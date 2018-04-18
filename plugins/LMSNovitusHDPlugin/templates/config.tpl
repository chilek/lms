{extends file="main.tpl"}
{block name=title}::: LMS :{$layout.pagetitle|striphtml} :::{/block}

{block name=novitus_content}
    <script type="text/javascript">
    <!--
    function setDateTime() {
        xajax_setTime(Date())
    }

    function setConfig() {
        let form = $('form[name="configOptionForm"]').serializeArray();
        xajax_setConfig(form)
    }

    function getConfig(el){
        xajax_getConfig(el)
    }

    function setOption(val) {
        $('#novitusConfigSetConfig').html('');
        $('input[name="configOptionValue"]').val(val)

    }

    -->
    </script>
    <style>
        .novitus-config-div {
            display: flex;
            align-items: center;
            align-content: center;
            border-bottom: 1px #979797 solid;
            padding: 10px 0 10px 0;
        }
        .novitus-config-div div {
            margin-right: 5px;
        }
        #configOptionForm select, #configOptionForm option {
            max-width: 200px;
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
                    <IMG src="img/money.gif" alt="">{trans("Printer config")}
                </TH>
            </TR>
            </THEAD>
            <TBODY>
            <TR>
                <TD colspan="2">
                    <div class="novitus-config-div">
                        <div>
                            <input type="button" value="{trans('Set current date')}" onclick="setDateTime();return false;">
                            {*<input type="text" name="novitus-actions-daily-report" value="{$smarty.now|date_format:"%Y/%m/%d"}" size="10" maxlength="10" placeholder="{trans("YYYY/mm/dd")}" {tip class="calendar" trigger="dailyreportdate" text="Enter daily report date in 'yyyy/mm/dd' format or click to choose date from calendar (optional)"}>*}
                        </div>
                        <div id="novitusConfigSetTime"></div>
                    </div>
                    <div class="novitus-config-div">
                        <div>
                            <form name="configOptionForm" id="configOptionForm">
                                <select name="configOption" onChange="getConfig(this.value);return false;">
                               <option value="-1">---{trans('Select option')}---</option>
                               {foreach from=$configoptions key=k item=v}
                                   <option value="{$k}">{$v}</option>
                               {/foreach}
                           </select>
                                <input type="text" name="configOptionValue" >
                            </form>
                            <input type="button" value="{trans('Set config option')}" onclick="setConfig();return false;">
                        </div>
                        <div id="novitusConfigSetConfig"></div>
                    </div>
                    <div class="novitus-config-div">
                        <div>
                            <select id="novitusConfigSelectErrorHandler">
                                <option value="silent">{trans('Silent - don\'t show errors on display')}</option>
                                <option value="display">{trans('Display - show errors on display')}</option>
                            </select>
                            <input type="button" value="{trans('Set error handler')}" onclick="xajax_setErrorHandler($('#novitusConfigSelectErrorHandler').val())" >
                        </div>
                        <div id="novitusConfigErrorHandler"></div>
                    </div>

                </TD>
            </TR>


            </TBODY>
        </TABLE>
    </div>

{/block}

