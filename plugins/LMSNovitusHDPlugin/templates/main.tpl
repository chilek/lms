{extends file="layout.html"}
{block name=title}::: LMS :{$layout.pagetitle|striphtml} :::{/block}
{block name=module_content}

    {$xajax}

    <script type="text/javascript">
    <!--
    let nLog;

    window.onload = function() {
        nLog = $('#novitusLog');
        nLog.hide()
        $('#stopPrintingButton').hide();
    };
    function showNovitusLog() {
        nLog.show();
    }
    -->
    </script>
    <style>
        #novitusLog {
            padding: 10px;
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid black;
            border-radius: 3px;
            overflow-y: auto;
            max-height: 800px;
        }

        input[type="button"] {
            margin-right: 10px;
        }

    </style>
    {block name=novitus_content}{/block}

    <div id="novitusLog"></div>

{/block}
