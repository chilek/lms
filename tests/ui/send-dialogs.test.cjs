// npm install --no-save jsdom@26 jquery@3 && node --test tests/ui/send-dialogs.test.cjs
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { JSDOM } = require('jsdom');
const jquery = require('jquery');

function source(path) {
    return readFileSync(resolve(__dirname, '../..', path), 'utf8');
}

async function fixture(kind, card, enabled) {
    const invoice = kind === 'invoice';
    const formName = card ? (invoice ? 'addbalance' : 'customerdocuments') : 'page';
    const formId = formName + '-form';
    const parent = card ? (invoice ? 'customer/customerbalancebox' : 'customer/customerdocuments')
        : kind + '/' + kind + 'list';
    const list = source('templates/default/' + parent + '.html');
    const dialog = source('templates/default/customer/customer' + kind + 'senddialog.html');
    const variables = {};
    for (const [, key, , value] of dialog.matchAll(/\{assign var="(\w+)" value=(["'])(.*?)\2\}/g)) {
        variables[key] = value;
    }
    if (!card) {
        for (const [, key, , value] of list.matchAll(/(\w+SendDialog\w+)=(["'])(.*?)\2/g)) {
            variables[key] = value;
        }
    }
    const constants = { DOC_INVOICE: 1, DOC_CNOTE: 3, DOC_DNOTE: 5, DOC_INVOICE_PRO: 6, DOC_ENTITY_DUPLICATE: 4 };
    const renderScript = script => script
        .replace(/\{json_encode\(\$(\w+)\)\}/g, (_, key) => JSON.stringify(variables[key]))
        .replace(/\{\$smarty.const.(\w+)\}/g, (_, key) => constants[key]);
    const markName = id => invoice && card ? 'marks[invoice][' + id + ']' : 'marks[' + id + ']';
    const inputs = [11, 22].map(id => `<input type="checkbox" class="document-checkbox lms-ui-multi-check"
        form="${formId}" name="${markName(id)}" value="${id}">`).join('');
    const attachment = invoice ? '' : `<input type="checkbox" class="lms-ui-multi-check" form="${formId}"
        name="attachment-marks[11][9]" value="9">`;
    const markup = enabled ? dialog.slice(dialog.indexOf('<div id='), dialog.indexOf('<script>'))
        .replace(/\{foreach [\s\S]*?\{\/foreach\}/g, '')
        .replace(/\{\$\w+SendCustomerId\|default:''\}/g, card ? '1' : '')
        .replace(/\{trans\("(.*?)"\)\}/g, '$1')
        .replace(/\{button [^}]*id="([^"]+)"[^}]*\}/g, '<button type="button" id="$1"></button>') : '';
    const dom = new JSDOM(`<form name="${formName}" id="${formId}"><div class="lms-ui-multi-check"><table>
        <tbody class="lms-ui-multi-check"><tr data-doctype="1"><td>${inputs}${attachment}</td></tr></tbody>
        <tfoot><tr><td><button type="button" id="send-${kind}s" data-send-documents="1"></button></td></tr></tfoot>
        </table></div></form>${markup}`, { runScripts: 'outside-only', url: 'https://lms.invalid/' });
    const w = dom.window;
    const $ = w.$ = jquery(w);
    const requests = [];
    const sends = [];
    let confirmations = 0;
    const listForm = w.document[formName] = w.document.getElementById(formId);
    listForm.submit = () => sends.push(listForm.action);
    w.$t = text => text;
    w.alertDialog = text => assert.fail(text);
    w.confirmDialog = () => { confirmations++; return $.Deferred().resolve(); };
    $.ajax = options => {
        const reply = $.Deferred();
        requests.push({ data: Object.fromEntries(options.data.map(x => [x.name, x.value])), reply });
        return reply;
    };
    if (enabled) {
        w.LmsUiDialog = function () {
            this.open = () => {};
            this.close = () => w.document.getElementById(kind + '-send-form').reset();
        };
        w.eval(renderScript(dialog.match(/<script>([\s\S]*?)<\/script>/)[1]));
        await new Promise(done => $(done));
    }
    const caller = card && !invoice ? source('js/templates/customer/customerdocuments.js') : list;
    const handler = caller.match(new RegExp("\\$\\('#send-" + kind + "s'\\)\\.click\\([\\s\\S]*?\\n\\t\\}\\);"))[0];
    const flag = kind + (card ? 'SendContacts' : 'SendDialog');
    w.eval(renderScript(handler.replace(/\{if isset\(\$(\w+)\)\}([\s\S]*?)\{\/if\}/g,
        (_, key, body) => enabled && key === flag ? body : '')));
    return { $, dom, requests, sends, confirmations: () => confirmations, markName,
        open: () => $('#send-' + kind + 's').triggerHandler('click'),
        select: (...ids) => $(listForm).find('input').each(function () {
            this.checked = ids.includes(Number(this.value));
        }) };
}

for (const kind of ['document', 'invoice']) {
    for (const card of [false, true]) {
        const label = kind + (card ? ' / customer card' : ' / global list');
        test(label + ' / reopen and submit previewed selection', async t => {
            const f = await fixture(kind, card, true);
            t.after(() => f.dom.window.close());
            const { $, markName } = f;
            const form = $('#' + kind + '-send-form');
            const field = key => form.find('[name="' + kind + 'send[' + key + ']"]');
            // Cancel an edited preview before switching customer or reopening the card.
            f.select(11);
            f.open();
            f.requests.at(-1).reply.resolve({ customerid: '1', subject: 'draft', body: 'draft' });
            field('extraemail').val('previous@example.invalid');
            $('#' + kind + '-send-cancel-button').triggerHandler('click');
            const selections = card ? [[11], [11]] : [[22], [22], [11, 22], [11]];
            for (const ids of selections) {
                const attachment = kind === 'document' && ids.includes(11);
                f.select(...ids, ...(attachment ? [9] : []));
                f.open();
                const request = f.requests.at(-1);
                const marks = Object.fromEntries(ids.map(id => [markName(id), String(id)]));
                if (attachment) marks['attachment-marks[11][9]'] = '9';
                assert.deepEqual(request.data, card ? { ...marks, customerid: '1' } : marks);
                f.select(ids.includes(11) ? 22 : 11); // list changes while the preview is in flight
                const mixed = ids.length > 1;
                const customer = mixed ? '' : String(ids[0] / 11);
                const email = customer + '@example.invalid';
                request.reply.resolve({ mixedCustomers: mixed, customerid: customer, subject: 's', body: 'b',
                    contacts: mixed ? [] : [{ contact: email, fullname: email, checked: true }] });
                assert.equal(field('customerid').val(), customer);
                assert.equal(field('extraemail').val(), '');
                assert.deepEqual(form.find(':checkbox[name$="[recipients][]"]:checked').map((_, e) => e.value).get(),
                    mixed ? [] : [email]);
                assert.equal($('#' + kind + '-send-recipients-box').css('display') === 'none', mixed);
                assert.notEqual(form.triggerHandler('submit'), false);
                const fields = form.serializeArray().filter(x => /^(marks|attachment-marks)\[/.test(x.name));
                assert.deepEqual(fields.map(x => [x.name, x.value]), Object.entries(marks));
                await new Promise(resolve => setTimeout(resolve, 1)); // submit closes and resets the dialog
            }
        });

        test(label + ' / disabled dialog uses existing confirmation', async t => {
            const f = await fixture(kind, card, false);
            t.after(() => f.dom.window.close());
            f.select(11);
            f.open();
            assert.equal(f.confirmations(), 1);
            assert.equal(f.requests.length, 0);
            assert.equal(f.sends.length, 1);
            const url = new URL(f.sends[0]);
            assert.equal(url.searchParams.get('m'), kind + 'send');
            if (kind === 'invoice') assert.equal(url.searchParams.get('marks'), card ? 'cash' : 'invoice');
        });
    }
}
