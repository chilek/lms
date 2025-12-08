#!/usr/bin/env node

/*jshint esversion: 8 */

const puppeteer = require('puppeteer-core');
const commander = require('commander');
const program = new commander.Command();
const fs = require('fs');
const http = require('http');
const { randomUUID } = require('crypto');

program
    .option("-i, --in-file <input-file>", "input file")
    .option("-o, --out-file <output-file>", "output file")
    .option("-f, --format <paper-format>", "output paper format", "A4")
    .option("-r, --orientation <paper-orientation>", "output paper orientation", "portrait")
    .option("-m, --media-type <screen|print|null>", "force specified media type", "print")
    .option("-w, --wait-until <load|domcontentloaded|networkidle0|networkidle2>", "wait for specified event in web browser", "load")
    .option("-p, --page-numbers", "print page numbers in output footer")
    .option("--margins <margins>", "set document margins")
    .parse(process.argv);

var options = program.opts();

var url = null;
if (options.inFile) {
    if (options.inFile.match(/^file:/) || !options.inFile.match(/^https?:/)) {
        var inFile = options.inFile.replace(/^file:/g, '');
        if (!fs.existsSync(inFile)) {
            console.error(`File ${inFile} does not exist!`);
            process.exit(1);
        }
        url = "file:" + inFile;
    } else {
        url = options.inFile;
    }
}

var outFile = options.outFile ? options.outFile : null;
var margins = options.margins ? options.margins : null;

if (["Letter", "Legal", "Tabloid", "Ledger", "A0", "A1", "A2", "A3", "A4", "A5", "A6"].lastIndexOf(options.format) == -1) {
    console.error('Invalid format value!');
    process.exit(1);
}

if (["portrait", "landscape"].lastIndexOf(options.orientation) == -1) {
    console.error('Invalid orientation value!');
    process.exit(1);
}
var landscape = options.orientation === "landscape";

if (["print", "screen", "null"].lastIndexOf(options.mediaType) == -1) {
    console.error("Invalid media type!");
    process.exit(1);
}

if (["load", "domcontentloaded", "networkidle0", "networkidle2"].lastIndexOf(options.waitUntil) == -1) {
    console.error("Invalid wait until value!");
    process.exit(1);
}

async function readStream(stream) {
    return new Promise((resolve, reject) => {
        let data = "";

        stream.on("data", chunk => data += chunk);
        stream.on("end", function() { resolve(data); });
        stream.on("error", error => reject(error));
    });
}

(async (options) => {
    let server = null;

    try {
        const browser = await puppeteer.connect({
            browserURL: "http://127.0.0.1:9222"
        });

        const page = await browser.newPage();

/*
        page.on('console', msg => console.log('PAGE LOG:', msg.type(), msg.text()));
        page.on('pageerror', err => console.error('PAGE ERROR:', err));
        page.on('requestfailed', req => console.error('REQ FAIL:', req.url(), req.failure()));
*/

        await page.emulateMediaType(options.mediaType);

        let finalUrl = url;

        if (!finalUrl) {
            // brak --in-file: czytamy HTML z stdin
            const content = await readStream(process.stdin);

            // tworzymy jednorazowy serwer HTTP, który zwróci ten HTML
            server = http.createServer((req, res) => {
                res.writeHead(200, {
                    "Content-Type": "text/html; charset=UTF-8",
                    "Cache-Control": "no-store"
                });
                res.end(content);
            });

            await new Promise(resolve => server.listen(0, resolve));
            const port = server.address().port;

            // unikalna ścieżka, żeby uniknąć cachy
            finalUrl = `http://127.0.0.1:${port}/doc-${randomUUID()}.html`;
        }

        await page.goto(finalUrl, {waitUntil: options.waitUntil, timeout: 0});

        var opts = {
            format: options.format,
            landscape: landscape,
            printBackground: true,
            timeout: 0
        }
        if (outFile) {
            opts.path = outFile;
        }
        if (options.pageNumbers) {
            opts.displayHeaderFooter = true;
            opts.headerTemplate = '<div></div>';
            opts.footerTemplate = '<div style="font-size: 10px; text-align: center; width: 100%;">' +
                '<span class="pageNumber"></span> / <span class="totalPages"></span>' +
                '</div>';
        }
        if (options.margins) {
            var margins = options.margins.split(',');
            margins.forEach(function(item, index, arr) {
                if (!item.match(/[a-z]+$/)) {
                    arr[index] = item + 'mm';
                }
            });
            var pdfMargin;
            switch (margins.length) {
                case 1:
                    pdfMargin = {
                        top: margins[0],
                        right: margins[0],
                        bottom: margins[0],
                        left: margins[0]
                    }
                    break;
                case 2:
                    pdfMargin = {
                        top: margins[0],
                        right: margins[1],
                        bottom: margins[0],
                        left: margins[1]
                    }
                    break;
                case 3:
                    pdfMargin = {
                        top: margins[0],
                        right: margins[1],
                        bottom: margins[2],
                        left: margins[1]
                    }
                    break;
                default:
                    pdfMargin = {
                        top: margins[0],
                        right: margins[1],
                        bottom: margins[2],
                        left: margins[3]
                    }
                    break;
            }
            opts.margin = pdfMargin;
        }

        const hyphenReadyExists = await page.evaluate(() => {
            return typeof window.MyVar !== 'undefined';
        });

        if (hyphenReadyExists) {
            await page.waitForFunction(
                () => window.__hyphenReady === true,
                { timeout: 5000 }
            );
        }

        const pdf = await page.pdf(opts);
        await page.close();

        await browser.disconnect();

        if (server) {
            await server.close();
        }

        if (!outFile) {
            process.stdout.write(pdf, (err) => {
                if (err) {
                    console.error(err);
                    process.exit(1);
                }
                process.exit(0);
            });
        }
    } catch (err) {
        console.error(err);
        if (server) {
            server.close();
        }
        process.exit(1);
    }
})(options);
