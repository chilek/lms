{trans("Page:")}
{if $pagination->getPage() > 1}
    <a href="?m={$layout.module}&page={math equation="x - 1" x=$pagination->getPage()}">{'&laquo;'|str_repeat:3}</a>
{/if}
{section name=pagination loop=$pagination->getPages() step=1 start=1}
    {if $pagination->getPage() == $smarty.section.pagination.index}
        [{$smarty.section.pagination.index}]
    {else}
        <a href="?m={$layout.module}&page={$smarty.section.pagination.index}">{$smarty.section.pagination.index}</a>
    {/if}
{/section}
{if $pagination->getPage() < $pagination->getPages()}
    <a href="?m={$layout.module}&page={math equation="x + 1" x=$pagination->getPage()}">{'&raquo;'|str_repeat:3}</a>
{/if}