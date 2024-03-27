	var devices = [];
	{if $devices}
		{foreach $devices as $device}
			devices.push({
				lon: "{$device.lon}",
				lat: "{$device.lat}",
				state: {$device.state},
				name: "{$device.name|replace:'"':'\\"'}",
				location: "{$device.location|default:""|escape}",
				ipaddr: "{$device.ipaddr}",
				nodeid: "{$device.nodeid}",
				id: {$device.id},
				type: "netdevinfo",
				url: "{$device.url}",
				comment: "{$device.comment}",
				radiosectors: [
				{foreach $device.radiosectors as $radiosector}
					{
						name: '{$radiosector.name}',
						technology: "{$radiosector.technology}",
						azimuth: {$radiosector.azimuth},
						width: {$radiosector.width},
						rsrange: {$radiosector.rsrange},
						frequency: '{if $radiosector.frequency}{t a=((float)$radiosector.frequency)|replace:",":"."}$a GHz{/t}{/if}',
						frequency2: '{if $radiosector.frequency2}{t a=((float)$radiosector.frequency2)|replace:",":"."}$a GHz{/t}{/if}',
						bandwidth: '{if $radiosector.bandwidth}{t a=((float)$radiosector.bandwidth*1000)|string_format:"%.0f"|replace:",":"."}$a MHz{/t}{/if}'
					} {if !$radiosector@last},{/if}
				{/foreach}
				]
			});
		{/foreach}
	{/if}

	var devlinks = [];
	{if $devlinks}
		{foreach $devlinks as $devlink}
			devlinks.push({
				netlinkid: {$devlink.netlinkid},
				src: {$devlink.src},
				dst: {$devlink.dst},
				srclon: {$devlink.srclon},
				srclat: {$devlink.srclat},
				dstlon: {$devlink.dstlon},
				dstlat: {$devlink.dstlat},
				points: JSON.parse('{json_encode($devlink.points)}'),
				type: "{$devlink.type}",
				technology: "{$devlink.technology}",
				speed: "{$devlink.speed}",
				typename: "{$devlink.typename}",
				technologyname: "{$devlink.technologyname}",
				speedname: "{$devlink.speedname}"
			});
		{/foreach}
	{/if}

	var nodes = [];
	{if $nodes}
		{foreach $nodes as $node}
			nodes.push({
				lon: "{$node.lon}",
				lat: "{$node.lat}",
				state: {$node.state},
				name: "{$node.name|replace:'"':'\\"'}",
				location: "{$node.location|default:""|escape}",
				ipaddr: "{$node.ipaddr}",
				id: {$node.id},
				type: "nodeinfo",
				url: "{$node.url}",
				comment: "{$node.comment}",
				linktype: "{$node.linktype}",
				linktypeicon: "{$node.linktypeicon}",
				linktypename: "{$node.linktypename}",
				linktechnology: "{$node.linktechnology}",
				linktechnologyname: "{$node.linktechnologyname}"
			});
		{/foreach}
	{/if}

	var nodelinks = [];
	{if $nodelinks}
		{foreach $nodelinks as $nodelink}
			nodelinks.push({
				nodelon: {$nodelink.nodelon},
				nodelat: {$nodelink.nodelat},
				netdevlon: {$nodelink.netdevlon},
				netdevlat: {$nodelink.netdevlat},
				type: "{$nodelink.type}",
				technology: "{$nodelink.technology}",
				speed: "{$nodelink.speed}",
				typename: "{$nodelink.typename}",
				technologyname: "{$nodelink.technologyname}",
				speedname: "{$nodelink.speedname}"
			});
		{/foreach}
	{/if}

	var ranges = [];
	{if $ranges}
		{foreach $ranges as $range}
			ranges.push({
				location: "{$range.location|escape}",
				lon: "{$range.longitude}",
				lat: "{$range.latitude}",
				existing: {$range.existing},
				typename: "{$range.typename}",
				technologyname: "{$range.technologyname}",
				speedname: "{$range.speedname}",
				rangetype: "{$range.type}",
				rangetypename: "{$range.rangetypename}",
				existingname: "{$range.existingname}",
				servicesname: "{$range.servicesname}"
			});
		{/foreach}
	{/if}
