{extends file="main.tpl"}
{block name=title}::: LMS :{$layout.pagetitle|striphtml} :::{/block}

{block name=novitus_content}
    <SCRIPT type="text/javascript">
        <!--
        let stop = false;
        let invoices;

        function showInvoices() {
            nLog.html('');
            nLog.show();
            if ($('form[name="fiscalizeinvoices"] select[name="division"]').val() == '0') {
                alert('{trans("Division not selected!")}');
                return;
            }
            stop = false;

            xajax_getInvoices(JSON.stringify($('form[name="fiscalizeinvoices"]').serializeArray()), true);
        }

        function startPrinting(res) {
            invoices = res;

            nLog.show();

            nLog.append('<p>There is ' +invoices.length+' invoices to print. <strong>Start sending invoices ...</strong></p>');
            printInvoice();

        }

        function stopPrinting() {
            stop = true;
            $('#startPrintingButton').show();
            $('#stopPrintingButton').hide();
        }


        function printInvoice(){

            $('#startPrintingButton').hide();
            $('#stopPrintingButton').show();

            if (stop) {
                nLog.append('<p><strong>{trans('Printing invoices has been stopped')}</strong></p>');
                $('#startPrintingButton').show();
                $('#stopPrintingButton').hide();
                return
            }

            if (invoices.length === 0) {
                nLog.show();
                nLog.append('<p><strong>{trans('No invoices left to print')}</strong></p>');
                $('#startPrintingButton').show();
                $('#stopPrintingButton').hide();
                return;
            }

            xajax_printInvoice(invoices.shift());


        }

        function fiscalize() {
            nLog.html('');
            nLog.show();
            if ($('form[name="fiscalizeinvoices"] select[name="division"]').val() == '0') {
                alert('{trans("Division not selected!")}');
                return;
            }
            stop = false;

            xajax_getInvoices(JSON.stringify($('form[name="fiscalizeinvoices"]').serializeArray()));

            // document.fiscalizeinvoices.action = "?m=novitushd&type=invoice&action=print";
            // document.fiscalizeinvoices.submit();
        }

        //-->
    </SCRIPT>

    <FORM method="post" name="fiscalizeinvoices" id="fiscalizeinvoices" action="?m=novitushd&type=invoice">
        <INPUT type="submit" class="hiddenbtn">

        <TABLE class="lmsbox">
            <COLGROUP>
                <COL style="width: 1%;">
                <COL style="width: 99%;">
            </COLGROUP>
            <THEAD>
            <TR>
                <TH scope="col">
                    <IMG src="img/money.gif" alt="">
                </TH>
                <TH scope="col" class="bold nobr">
                    {trans("Invoices")}
                </TH>
            </TR>
            </THEAD>
            <TBODY>
            <TR>
                <TD class="container" width="100%" colspan="2">
                    <TABLE width="100%" cellpadding="3">
                        <TR>
                            <TD style="width: 1%;">
                                <TABLE width="100%">
                                    <TR>
                                        <TD style="width: 1%;">
                                            <IMG src="img/division.gif" alt="">
                                        </TD>
                                        <TD style="width: 1%;" class="bold nobr">
                                            {trans("Division")}:
                                        </TD>
                                        <TD style="width: 98%;" class="nobr">
                                            <SELECT size="1" name="division" {tip text="Select division"}>
                                                <OPTION value="0">- {trans("all")} -</OPTION>
                                                {foreach $divisions as $division}
                                                    <OPTION value="{$division.id}">{$division.shortname}</OPTION>
                                                {/foreach}
                                            </SELECT>
                                        </TD>
                                    </TR>
                                    <TR>
                                        <TD style="width: 1%;">
                                            <IMG src="img/customer.gif" alt="">
                                        </TD>
                                        <TD style="width: 1%;" class="bold nobr">
                                            {trans("Customer:")}
                                        </TD>
                                        <TD style="width: 98%;" class="nobr">
                                            {customerlist form="fiscalizeinvoices" customers=$customers selectname="cust" inputname="customer" firstoption="- all customers -" selecttip="You can select customer to limit results" inputtip="Enter customer ID or leave empty for all customers"}
                                        </TD>
                                    </TR>
                                    <tr>
                                        <td><img src="img/customer.gif" alt=""></td>
                                        <td class="bold">{trans("Customers")}:</td>
                                        <td>
                                            <select name="customer_type">
                                                <option value="-1">{trans("- all customers -")}</option>
                                                <option value="{$smarty.const.CTYPES_PRIVATE}" selected>{trans("private person")}</option>
                                                <option value="{$smarty.const.CTYPES_COMPANY}">{trans("legal entity")}</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <TR>
                                        <TD style="width: 1%;">
                                            <IMG src="img/group.gif" alt="">
                                        </TD>
                                        <TD style="width: 1%;" class="bold nobr">
                                            {trans("Group:")}
                                        </TD>
                                        <TD style="width: 98%;" class="nobr">
                                            <SELECT size="1" name="group">
                                                <OPTION value="0" SELECTED>{trans("- all groups -")}</OPTION>
                                                {section name="customergroups" loop=$customergroups}
                                                    <OPTION value="{$customergroups[customergroups].id}">{$customergroups[customergroups].name|truncate:30:"...":true}</OPTION>
                                                {/section}
                                            </SELECT>
                                            <INPUT type="checkbox" name="groupexclude"
                                                   id="invoices-groupexclude" {if $listdata.groupexclude} CHECKED{/if}><label
                                                    for="invoices-groupexclude">{trans("exclude group")}</label>
                                        </TD>
                                    </TR>
                                </TABLE>
                            </TD>
                            <TD style="width: 99%;">
                                <TABLE style="width: 100%;">
                                    <TR>
                                        <TD style="width: 1%;">
                                            <IMG src="img/calendar.gif" alt="">
                                        </TD>
                                        <TD style="" class="nobr" colspan="3">
                                            <span class="bold nobr">{trans("Period:")}</span>&nbsp;
                                            {trans("From")}&nbsp;<INPUT type="text" name="invoicefrom" SIZE="10"
                                                                        maxlength="10"
                                                                        placeholder="{trans("yyyy/mm/dd")}" {tip class="calendar" text="Enter date in 'yyyy/mm/dd' format (empty field means current date) or click to choose it from calendar"}>
                                            {trans("To")}&nbsp;<INPUT type="text" name="invoiceto" SIZE="10"
                                                                      maxlength="10"
                                                                      placeholder="{trans("yyyy/mm/dd")}" {tip class="calendar" text="Enter date in 'yyyy/mm/dd' format (empty field means current date) or click to choose it from calendar"}>
                                        </TD>
                                        {*<TD style="width: 1%;">*}
                                            {*<IMG src="img/info.gif" alt="">*}
                                        {*</TD>*}
                                        {*<TD style="width: 97%; white-space: nowrap;">*}
                                            {*<p>free area</p>*}
                                        {*</TD>*}
                                    </TR>
                                    <TR>
                                        <TD colspan="2">
                                            <INPUT type="checkbox" name="showonlynotfiscalized"
                                                   id="invoices-showonlynotfiscalized" checked>
                                            <label for="invoices-showonlynotfiscalized">{trans("Select only NOT fiscalized")}</label>
                                            <br>
                                            <a href="javascript:showInvoices();">{trans("Show invoices to be printed")} <img src="img/doc.gif" alt=""></a>
                                        </TD>
                                    </TR>
                                </TABLE>
                            </TD>
                        </TR>
                    </TABLE>
                </TD>

            </TR>
            <TR>
                <TD style="width: 100%;" colspan="2" align="right">
                    <a id="startPrintingButton" href="javascript:fiscalize();">{trans("Start printing")} <img src="img/printr.gif" alt=""></a>
                    <a id="stopPrintingButton" href="javascript:stopPrinting();" class="red">{trans("STOP printing")} <img src="img/cancel.gif" alt=""></a>

                </TD>
            </TR>
            </TBODY>
        </TABLE>
    </FORM>
{/block}

