{extends file="main.tpl"}
{block name=title}::: LMS :{$layout.pagetitle|striphtml} :::{/block}

{block name=novitus_content}
    <div class="lf wq">
        <TABLE class="lmsbox">
            <COLGROUP>
                <COL style="width: 30%;">
                <COL style="width: 70%;">
            </COLGROUP>
            <THEAD>
            <TR>
                <TH scope="col" class="bold" colspan="2">
                    <IMG src="img/money.gif" alt="">{trans("Printer Information")}
                </TH>
            </TR>
            </THEAD>
            <TBODY>
            {cycle values="lucid,light" print=false}
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Printer Version")}:</TD>
                <TD class="bold">{$ver.attr.version}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Fiscal Memory Sze")}:</TD>
                <TD class="bold">{$fiscal.attr.fiscalmemorysize}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Record size")}:</TD>
                <TD class="bold">{$fiscal.attr.recordsize}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Fiscal")}:</TD>
                <TD class="bold">{$fiscal.attr.fiscal}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Unique No.")}:</TD>
                <TD class="bold">{$fiscal.attr.uniqueno}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("NIP")}:</TD>
                <TD class="bold">{$fiscal.attr.nip}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Max Records Count")}:</TD>
                <TD class="bold">{$fiscal.attr.maxrecordscount}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Records Count")}:</TD>
                <TD class="bold">{$fiscal.attr.recordscount}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Max Reports Count")}:</TD>
                <TD class="bold">{$fiscal.attr.maxreportscount}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Reports Count")}:</TD>
                <TD class="bold">{$fiscal.attr.reportscount}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Reset Max Count")}:</TD>
                <TD class="bold">{$fiscal.attr.resetmaxcount}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Reset Count")}:</TD>
                <TD class="bold">{$fiscal.attr.resetcount}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Tax Rates PRG Limit")}:</TD>
                <TD class="bold">{$fiscal.attr.taxratesprglimit}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Tax Rates PRG")}:</TD>
                <TD class="bold">{$fiscal.attr.taxratesprg}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Currency Change PRG Limit")}:</TD>
                <TD class="bold">{$fiscal.attr.currencychangeprglimit}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Currency Chante PRG")}:</TD>
                <TD class="bold">{$fiscal.attr.currencychangeprg}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Fiscal Start Date")}:</TD>
                <TD class="bold">{$fiscal.attr.fiscalstartdate}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Fiscal Stop Date")}:</TD>
                <TD class="bold">{$fiscal.attr.fiscalstopdate}</TD>
            </TR>
            <TR class="{cycle}">
                <TD class="valign-top">{trans("Currency Name")}:</TD>
                <TD class="bold">{$fiscal.attr.currencyname}</TD>
            </TR>
            {*<TR>*}
            {*<TD style="width: 100%;" colspan="2" align="right">*}
            {*<A href="javascript:document.printimportlist.target='_blank';document.printimportlist.submit();">Drukuj <IMG src="img/print.gif" alt="" hspace="2"></A>*}
            {*</TD>*}
            {*</TR>*}
            </TBODY>
        </TABLE>
    </div>
    <div class="lf wh">
        <TABLE class="lmsbox">
            <COLGROUP>
                <COL style="width: 15%;">
                <COL style="width: 85%;">
            </COLGROUP>
            <THEAD>
            <TR>
                <TH scope="col" class="bold" colspan="2">
                    <IMG src="img/money.gif" alt="">{trans("Printer Config")}
                </TH>
            </TR>
            </THEAD>
            <TBODY>
            {cycle values="lucid,light" print=false}
            {foreach from=$config item=val}
                <TR class="{cycle}">
                    <TD class="valign-top text-right bold">{trans($val.value)}:</TD>
                    <TD class="">{$val.attr.desc}</TD>
                </TR>
            {/foreach}
            </TBODY>
        </TABLE>
    </div>
    <div class="lf wq">
        <div class="">
            <TABLE class="lmsbox">
                <COLGROUP>
                    <COL style="width: 50%;">
                    <COL style="width: 50%;">
                </COLGROUP>
                <THEAD>
                <TR>
                    <TH scope="col" class="bold" colspan="2">
                        <IMG src="img/money.gif" alt="">{trans("Programed tax rates")}
                    </TH>
                </TR>
                </THEAD>
                <TBODY>
                {cycle values="lucid,light" print=false}
                {foreach from=$taxes item=tax}
                    <TR class="{cycle}">
                        <TD class="valign-top text-right">{$tax.attr.name}:</TD>
                        <TD class="bold">{$tax.value}</TD>
                    </TR>
                {/foreach}
                </TBODY>
            </TABLE>
        </div>
        <div class="">
            <TABLE class="lmsbox">
                <COLGROUP>
                    <COL style="width: 50%;">
                    <COL style="width: 50%;">
                </COLGROUP>
                <THEAD>
                <TR>
                    <TH scope="col" class="bold" colspan="2">
                        <IMG src="img/money.gif" alt="">{trans("Const tax rates in class")}
                    </TH>
                </TR>
                </THEAD>
                <TBODY>
                {cycle values="lucid,light" print=false}
                {foreach from=$consttaxes item=t key=k}
                    <TR class="{cycle}">
                        <TD class="valign-top text-right">{$t}:</TD>
                        <TD class="bold">{$k}&percnt;</TD>
                    </TR>
                {/foreach}
                </TBODY>
            </TABLE>
        </div>
    </div>
    <div style="clear: both"></div>

{/block}
