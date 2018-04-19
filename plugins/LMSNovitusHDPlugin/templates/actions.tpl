{extends file="main.tpl"}
{block name=title}::: LMS :{$layout.pagetitle|striphtml} :::{/block}

{block name=novitus_content}
    <script type="text/javascript">
    <!--

    function printDailyReport() {

        xajax_printDailyReport($('input[name="novitus-actions-daily-report"]').val())
    }

    function printMonthlyReport() {

        let fromdate = $('input[name="novitus-actions-monthly-report-from"]').val();
        let todate = $('input[name="novitus-actions-monthly-report-to"]').val();
        let kind = $('select[name="kind"]').val();
        if (fromdate === '' && todate === '') {
            alert('{trans('Set dates')}')
            return;
        }

        if (fromdate > todate) {
            alert('{trans('Invalid period')}')
            return
        }

        xajax_printPeriodReport(fromdate, todate, kind);
    }

    function getLastTranaction(){
        xajax_getLastTransaction()
    }

    function getCurrentTranaction() {
        xajax_getCurrentTransaction()
    }

    -->
    </script>
    <style>
        .novitus-actions-div {
            display: flex;
            align-items: center;
            align-content: center;
            border-bottom: 1px #979797 solid;
            padding: 10px 0 10px 0;
        }
        .novitus-actions-div div {
            margin-right: 5px;
        }
    </style>

    <div class="wf">
        <TABLE class="lmsbox">
            <COLGROUP>
                <COL>
                <COL>
            </COLGROUP>
            <THEAD>
            <TR>
                <TH scope="col" class="bold" colspan="2">
                    <IMG src="img/money.gif" alt="">{trans("Printer actions")}
                </TH>
            </TR>
            </THEAD>
            <TBODY>
            <TR>
                <TD colspan="2">
                    <div class="novitus-actions-div">
                        <div>
                            <input type="button" value="{trans('Print daily report')}" onclick="printDailyReport();return false;">
                            <input type="text" name="novitus-actions-daily-report" value="{$smarty.now|date_format:"%Y/%m/%d"}" size="10" maxlength="10" placeholder="{trans("YYYY/mm/dd")}" {tip class="calendar" trigger="dailyreportdate" text="Enter daily report date in 'yyyy/mm/dd' format or click to choose date from calendar (optional)"}>
                        </div>
                        <div id="novitusActionsDailyReport"></div>
                    </div>
                    <div class="novitus-actions-div">
                        <div>
                            <input type="button" value="{trans('Print report')}" onclick="printMonthlyReport();return false;">
                            {trans("from:")} <INPUT type="TEXT" name="novitus-actions-monthly-report-from" value="" placeholder="{trans("yyyy/mm/dd")}" {tip class="calendar" text="Enter start date in YYYY/MM/DD format (optional)" trigger="fromdate"} size="10">&nbsp;
                            {trans("to:")} <INPUT type="TEXT" name="novitus-actions-monthly-report-to" value="" placeholder="{trans("yyyy/mm/dd")}" {tip class="calendar" text="Enter end date in YYYY/MM/DD format (optional)" trigger="todate"} size="10">
                            <select name="kind">
                                <option value="monthlyfull" selected>{trans('Monthly full - fiscalized')}</option>
                                <option value="full">{trans('Full from given dates - fiscalized')}</option>
                                <option value="salesummary">{trans('Sales summary - not fiscalized')}</option>
                                <option value="monthlysummary">{trans('Monthly summary - not fiscalized')}</option>
                                <option value="billingfull">{trans('Full financial settlement')}</option>
                                <option value="billingsummary">{trans('Financial settlement from given dates')}</option>
                            </select>

                        </div>
                        <div id="novitusActionsMonthlyReport"></div>
                    </div>
                    <div class="novitus-actions-div">
                        <input type="button" value="{trans('Get last tranaction data')}" onclick="getLastTranaction();return false;">

                        <div id="novitusActionsGetLastTransaction"></div>
                    </div>
                    <div class="novitus-actions-div">
                        <input type="button" value="{trans('Get current tranaction data')}" onclick="getCurrentTranaction();return false;">

                        <div id="novitusActionsCurrentTransaction"></div>
                    </div>
                    <div class="novitus-actions-div">
                        <input type="button" value="{trans('Get last error')}" onclick="xajax_getLastError();return false;">

                        <div id="novitusActionsLastError"></div>
                    </div>
                </TD>
            </TR>


            </TBODY>
        </TABLE>
    </div>

{/block}

