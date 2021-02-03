#!/usr/bin/env node

/*jshint esversion: 8 */

const puppeteer = require('puppeteer-core');
const program = require('commander');
const fs = require('fs');

program
    .option("-i, --in-file <input-file>", "input file")
    .option("-o, --out-file <output-file>", "output file")
    .option("-f, --format <paper-format>", "output paper format", "A4")
    .option("-r, --orientation <paper-orientation>", "output paper orientation", "portrait")
    .option("-m, --media-type <screen|print|null>", "force specified media type", "print")
    .option("-w, --wait-until <load|domcontentloaded|networkidle0|networkidle2>", "wait for specified event in web browser", "load")
    .parse(process.argv);

var url = null;
if (program.inFile) {
    if (program.inFile.match(/^file:/) || !program.inFile.match(/^https?:/)) {
        var inFile = program.inFile.replace(/^file:/g, '');
        if (!fs.existsSync(inFile)) {
            console.error(`File ${inFile} does not exist!`);
            process.exit(1);
        }
        url = "file:" + inFile;
    } else {
        url = program.inFile;
    }
}

var outFile = program.outFile ? program.outFile : null;

if (["Letter", "Legal", "Tabloid", "Ledger", "A0", "A1", "A2", "A3", "A4", "A5", "A6"].lastIndexOf(program.format) == -1) {
    console.error('Invalid format value!');
    process.exit(1);
}

if (["portrait", "landscape"].lastIndexOf(program.orientation) == -1) {
    console.error('Invalid orientation value!');
    process.exit(1);
}
var landscape = program.orientation === "landscape";

if (["print", "screen", "null"].lastIndexOf(program.mediaType) == -1) {
    console.error("Invalid media type!");
    process.exit(1);
}

if (["load", "domcontentloaded", "networkidle0", "networkidle2"].lastIndexOf(program.waitUntil) == -1) {
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

(async () => {
    try {
        const browser = await puppeteer.connect({
            browserURL: "http://127.0.0.1:9222"
        });

        const page = await browser.newPage();
        await page.emulateMediaType(program.mediaType);
        if (url) {
            await page.goto(url, {waitUntil: program.waitUntil});
        } else {
            const content = await readStream(process.stdin);
            await page.setContent(content, {waitUntil: program.waitUntil});
        }
        var options = {
            format: program.format,
            landscape: landscape,
            printBackground: true
        }
        if (outFile) {
            options.path = outFile;
        }
        const pdf = await page.pdf(options);
        await page.close();

        await browser.disconnect();

        if (!outFile) {
            process.stdout.write(pdf);
        }
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
})();
