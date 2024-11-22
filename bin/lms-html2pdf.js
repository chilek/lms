#!/usr/bin/env node

/*jshint esversion: 8 */

const puppeteer = require('puppeteer-core');
const commander = require('commander');
const program = new commander.Command();
const fs = require('fs');

program
    .option("-i, --in-file <input-file>", "input file")
    .option("-o, --out-file <output-file>", "output file")
    .option("-f, --format <paper-format>", "output paper format", "A4")
    .option("-r, --orientation <paper-orientation>", "output paper orientation", "portrait")
    .option("-m, --media-type <screen|print|null>", "force specified media type", "print")
    .option("-w, --wait-until <load|domcontentloaded|networkidle0|networkidle2>", "wait for specified event in web browser", "load")
    .option("-p, --page-numbers", "print page numbers in output footer")
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
    try {
        const browser = await puppeteer.connect({
            browserURL: "http://127.0.0.1:9222"
        });

        const page = await browser.newPage();
        await page.emulateMediaType(options.mediaType);
        if (url) {
            await page.goto(url, {waitUntil: options.waitUntil});
        } else {
            const content = await readStream(process.stdin);
            await page.setContent(content, {waitUntil: options.waitUntil, timeout: 0});
        }
        var opts = {
            format: options.format,
            landscape: landscape,
            printBackground: true,
            timeout: 0
        }
        if (outFile) {
            opts.path = outFile;
        }
        if (pageNumbers) {
            opts.displayHeaderFooter = true;
            opts.footerTemplate = '<div style="font-size: 10px; text-align: center; width: 100%;">' +
                '<span class="pageNumber"></span> / <span class="totalPages"></span>' +
                '</div>';
        }

        const pdf = await page.pdf(opts);
        await page.close();

        await browser.disconnect();

        if (!outFile) {
            process.stdout.write(pdf);
        }
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
})(options);
