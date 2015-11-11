<span class="bold">
{trans("Page:")}
{if $pagination->getPage() > 1}
    <a href="?m={$layout.module}&page={math equation="x - 1" x=$pagination->getPage()}">
{/if}
    {'&laquo;'|str_repeat:3}
{if $pagination->getPage() > 1}
    </a>
{/if}
{section name=pagination loop=$pagination->getPages()+1 step=1 start=1}
    {if $pagination->getPage() == $smarty.section.pagination.index}
        [{$smarty.section.pagination.index}]
    {else}
        <a href="?m={$layout.module}&page={$smarty.section.pagination.index}">{$smarty.section.pagination.index}</a>
    {/if}
{/section}
{if $pagination->getPage() <= $pagination->getPages()}
    <a href="?m={$layout.module}&page={math equation="x + 1" x=$pagination->getPage()}">
{/if}
    {'&raquo;'|str_repeat:3}
{if $pagination->getPage() <= $pagination->getPages()}
    </a>
{/if}
</span>
    ({t a=$pagination->getFirstOnPage() b=$pagination->getLastOnPage() c=$pagination->getTotal()}records $a - $b of $c{/t})
