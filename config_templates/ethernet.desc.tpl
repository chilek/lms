<? foreach from=$nodes item=node ?>
<? $node.mac|lower ?>:<? $node.name|upper ?>
<? /foreach ?>
