<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */
# http://data.iana.org/TLD/tlds-alpha-by-domain.txt
# Version 2022071400, Last Updated Thu Jul 14 07:07:01 2022 UTC
$valid_tlds = array(
    'aaa', 'aarp', 'abarth', 'abb', 'abbott', 'abbvie', 'abc', 'able', 'abogado',
    'abudhabi', 'ac', 'academy', 'accenture', 'accountant', 'accountants', 'aco',
    'actor', 'ad', 'adac', 'ads', 'adult', 'ae', 'aeg', 'aero', 'aetna', 'af',
    'afl', 'africa', 'ag', 'agakhan', 'agency', 'ai', 'aig', 'airbus', 'airforce',
    'airtel', 'akdn', 'al', 'alfaromeo', 'alibaba', 'alipay', 'allfinanz',
    'allstate', 'ally', 'alsace', 'alstom', 'am', 'amazon', 'americanexpress',
    'americanfamily', 'amex', 'amfam', 'amica', 'amsterdam', 'analytics',
    'android', 'anquan', 'anz', 'ao', 'aol', 'apartments', 'app', 'apple', 'aq',
    'aquarelle', 'ar', 'arab', 'aramco', 'archi', 'army', 'arpa', 'art', 'arte',
    'as', 'asda', 'asia', 'associates', 'at', 'athleta', 'attorney', 'au',
    'auction', 'audi', 'audible', 'audio', 'auspost', 'author', 'auto', 'autos',
    'avianca', 'aw', 'aws', 'ax', 'axa', 'az', 'azure', 'ba', 'baby', 'baidu',
    'banamex', 'bananarepublic', 'band', 'bank', 'bar', 'barcelona', 'barclaycard',
    'barclays', 'barefoot', 'bargains', 'baseball', 'basketball', 'bauhaus',
    'bayern', 'bb', 'bbc', 'bbt', 'bbva', 'bcg', 'bcn', 'bd', 'be', 'beats',
    'beauty', 'beer', 'bentley', 'berlin', 'best', 'bestbuy', 'bet', 'bf', 'bg',
    'bh', 'bharti', 'bi', 'bible', 'bid', 'bike', 'bing', 'bingo', 'bio', 'biz',
    'bj', 'black', 'blackfriday', 'blockbuster', 'blog', 'bloomberg', 'blue', 'bm',
    'bms', 'bmw', 'bn', 'bnpparibas', 'bo', 'boats', 'boehringer', 'bofa', 'bom',
    'bond', 'boo', 'book', 'booking', 'bosch', 'bostik', 'boston', 'bot',
    'boutique', 'box', 'br', 'bradesco', 'bridgestone', 'broadway', 'broker',
    'brother', 'brussels', 'bs', 'bt', 'bugatti', 'build', 'builders', 'business',
    'buy', 'buzz', 'bv', 'bw', 'by', 'bz', 'bzh', 'ca', 'cab', 'cafe', 'cal',
    'call', 'calvinklein', 'cam', 'camera', 'camp', 'cancerresearch', 'canon',
    'capetown', 'capital', 'capitalone', 'car', 'caravan', 'cards', 'care',
    'career', 'careers', 'cars', 'casa', 'case', 'cash', 'casino', 'cat',
    'catering', 'catholic', 'cba', 'cbn', 'cbre', 'cbs', 'cc', 'cd', 'center',
    'ceo', 'cern', 'cf', 'cfa', 'cfd', 'cg', 'ch', 'chanel', 'channel', 'charity',
    'chase', 'chat', 'cheap', 'chintai', 'christmas', 'chrome', 'church', 'ci',
    'cipriani', 'circle', 'cisco', 'citadel', 'citi', 'citic', 'city', 'cityeats',
    'ck', 'cl', 'claims', 'cleaning', 'click', 'clinic', 'clinique', 'clothing',
    'cloud', 'club', 'clubmed', 'cm', 'cn', 'co', 'coach', 'codes', 'coffee',
    'college', 'cologne', 'com', 'comcast', 'commbank', 'community', 'company',
    'compare', 'computer', 'comsec', 'condos', 'construction', 'consulting',
    'contact', 'contractors', 'cooking', 'cookingchannel', 'cool', 'coop',
    'corsica', 'country', 'coupon', 'coupons', 'courses', 'cpa', 'cr', 'credit',
    'creditcard', 'creditunion', 'cricket', 'crown', 'crs', 'cruise', 'cruises',
    'cu', 'cuisinella', 'cv', 'cw', 'cx', 'cy', 'cymru', 'cyou', 'cz', 'dabur',
    'dad', 'dance', 'data', 'date', 'dating', 'datsun', 'day', 'dclk', 'dds', 'de',
    'deal', 'dealer', 'deals', 'degree', 'delivery', 'dell', 'deloitte', 'delta',
    'democrat', 'dental', 'dentist', 'desi', 'design', 'dev', 'dhl', 'diamonds',
    'diet', 'digital', 'direct', 'directory', 'discount', 'discover', 'dish',
    'diy', 'dj', 'dk', 'dm', 'dnp', 'do', 'docs', 'doctor', 'dog', 'domains',
    'dot', 'download', 'drive', 'dtv', 'dubai', 'dunlop', 'dupont', 'durban',
    'dvag', 'dvr', 'dz', 'earth', 'eat', 'ec', 'eco', 'edeka', 'edu', 'education',
    'ee', 'eg', 'email', 'emerck', 'energy', 'engineer', 'engineering',
    'enterprises', 'epson', 'equipment', 'er', 'ericsson', 'erni', 'es', 'esq',
    'estate', 'et', 'etisalat', 'eu', 'eurovision', 'eus', 'events', 'exchange',
    'expert', 'exposed', 'express', 'extraspace', 'fage', 'fail', 'fairwinds',
    'faith', 'family', 'fan', 'fans', 'farm', 'farmers', 'fashion', 'fast',
    'fedex', 'feedback', 'ferrari', 'ferrero', 'fi', 'fiat', 'fidelity', 'fido',
    'film', 'final', 'finance', 'financial', 'fire', 'firestone', 'firmdale',
    'fish', 'fishing', 'fit', 'fitness', 'fj', 'fk', 'flickr', 'flights', 'flir',
    'florist', 'flowers', 'fly', 'fm', 'fo', 'foo', 'food', 'foodnetwork',
    'football', 'ford', 'forex', 'forsale', 'forum', 'foundation', 'fox', 'fr',
    'free', 'fresenius', 'frl', 'frogans', 'frontdoor', 'frontier', 'ftr',
    'fujitsu', 'fun', 'fund', 'furniture', 'futbol', 'fyi', 'ga', 'gal', 'gallery',
    'gallo', 'gallup', 'game', 'games', 'gap', 'garden', 'gay', 'gb', 'gbiz', 'gd',
    'gdn', 'ge', 'gea', 'gent', 'genting', 'george', 'gf', 'gg', 'ggee', 'gh',
    'gi', 'gift', 'gifts', 'gives', 'giving', 'gl', 'glass', 'gle', 'global',
    'globo', 'gm', 'gmail', 'gmbh', 'gmo', 'gmx', 'gn', 'godaddy', 'gold',
    'goldpoint', 'golf', 'goo', 'goodyear', 'goog', 'google', 'gop', 'got', 'gov',
    'gp', 'gq', 'gr', 'grainger', 'graphics', 'gratis', 'green', 'gripe',
    'grocery', 'group', 'gs', 'gt', 'gu', 'guardian', 'gucci', 'guge', 'guide',
    'guitars', 'guru', 'gw', 'gy', 'hair', 'hamburg', 'hangout', 'haus', 'hbo',
    'hdfc', 'hdfcbank', 'health', 'healthcare', 'help', 'helsinki', 'here',
    'hermes', 'hgtv', 'hiphop', 'hisamitsu', 'hitachi', 'hiv', 'hk', 'hkt', 'hm',
    'hn', 'hockey', 'holdings', 'holiday', 'homedepot', 'homegoods', 'homes',
    'homesense', 'honda', 'horse', 'hospital', 'host', 'hosting', 'hot', 'hoteles',
    'hotels', 'hotmail', 'house', 'how', 'hr', 'hsbc', 'ht', 'hu', 'hughes',
    'hyatt', 'hyundai', 'ibm', 'icbc', 'ice', 'icu', 'id', 'ie', 'ieee', 'ifm',
    'ikano', 'il', 'im', 'imamat', 'imdb', 'immo', 'immobilien', 'in', 'inc',
    'industries', 'infiniti', 'info', 'ing', 'ink', 'institute', 'insurance',
    'insure', 'int', 'international', 'intuit', 'investments', 'io', 'ipiranga',
    'iq', 'ir', 'irish', 'is', 'ismaili', 'ist', 'istanbul', 'it', 'itau', 'itv',
    'jaguar', 'java', 'jcb', 'je', 'jeep', 'jetzt', 'jewelry', 'jio', 'jll', 'jm',
    'jmp', 'jnj', 'jo', 'jobs', 'joburg', 'jot', 'joy', 'jp', 'jpmorgan', 'jprs',
    'juegos', 'juniper', 'kaufen', 'kddi', 'ke', 'kerryhotels', 'kerrylogistics',
    'kerryproperties', 'kfh', 'kg', 'kh', 'ki', 'kia', 'kids', 'kim', 'kinder',
    'kindle', 'kitchen', 'kiwi', 'km', 'kn', 'koeln', 'komatsu', 'kosher', 'kp',
    'kpmg', 'kpn', 'kr', 'krd', 'kred', 'kuokgroup', 'kw', 'ky', 'kyoto', 'kz',
    'la', 'lacaixa', 'lamborghini', 'lamer', 'lancaster', 'lancia', 'land',
    'landrover', 'lanxess', 'lasalle', 'lat', 'latino', 'latrobe', 'law', 'lawyer',
    'lb', 'lc', 'lds', 'lease', 'leclerc', 'lefrak', 'legal', 'lego', 'lexus',
    'lgbt', 'li', 'lidl', 'life', 'lifeinsurance', 'lifestyle', 'lighting', 'like',
    'lilly', 'limited', 'limo', 'lincoln', 'linde', 'link', 'lipsy', 'live',
    'living', 'lk', 'llc', 'llp', 'loan', 'loans', 'locker', 'locus', 'loft',
    'lol', 'london', 'lotte', 'lotto', 'love', 'lpl', 'lplfinancial', 'lr', 'ls',
    'lt', 'ltd', 'ltda', 'lu', 'lundbeck', 'luxe', 'luxury', 'lv', 'ly', 'ma',
    'macys', 'madrid', 'maif', 'maison', 'makeup', 'man', 'management', 'mango',
    'map', 'market', 'marketing', 'markets', 'marriott', 'marshalls', 'maserati',
    'mattel', 'mba', 'mc', 'mckinsey', 'md', 'me', 'med', 'media', 'meet',
    'melbourne', 'meme', 'memorial', 'men', 'menu', 'merckmsd', 'mg', 'mh',
    'miami', 'microsoft', 'mil', 'mini', 'mint', 'mit', 'mitsubishi', 'mk', 'ml',
    'mlb', 'mls', 'mm', 'mma', 'mn', 'mo', 'mobi', 'mobile', 'moda', 'moe', 'moi',
    'mom', 'monash', 'money', 'monster', 'mormon', 'mortgage', 'moscow', 'moto',
    'motorcycles', 'mov', 'movie', 'mp', 'mq', 'mr', 'ms', 'msd', 'mt', 'mtn',
    'mtr', 'mu', 'museum', 'music', 'mutual', 'mv', 'mw', 'mx', 'my', 'mz', 'na',
    'nab', 'nagoya', 'name', 'natura', 'navy', 'nba', 'nc', 'ne', 'nec', 'net',
    'netbank', 'netflix', 'network', 'neustar', 'new', 'news', 'next',
    'nextdirect', 'nexus', 'nf', 'nfl', 'ng', 'ngo', 'nhk', 'ni', 'nico', 'nike',
    'nikon', 'ninja', 'nissan', 'nissay', 'nl', 'no', 'nokia',
    'northwesternmutual', 'norton', 'now', 'nowruz', 'nowtv', 'np', 'nr', 'nra',
    'nrw', 'ntt', 'nu', 'nyc', 'nz', 'obi', 'observer', 'office', 'okinawa',
    'olayan', 'olayangroup', 'oldnavy', 'ollo', 'om', 'omega', 'one', 'ong', 'onl',
    'online', 'ooo', 'open', 'oracle', 'orange', 'org', 'organic', 'origins',
    'osaka', 'otsuka', 'ott', 'ovh', 'pa', 'page', 'panasonic', 'paris', 'pars',
    'partners', 'parts', 'party', 'passagens', 'pay', 'pccw', 'pe', 'pet', 'pf',
    'pfizer', 'pg', 'ph', 'pharmacy', 'phd', 'philips', 'phone', 'photo',
    'photography', 'photos', 'physio', 'pics', 'pictet', 'pictures', 'pid', 'pin',
    'ping', 'pink', 'pioneer', 'pizza', 'pk', 'pl', 'place', 'play', 'playstation',
    'plumbing', 'plus', 'pm', 'pn', 'pnc', 'pohl', 'poker', 'politie', 'porn',
    'post', 'pr', 'pramerica', 'praxi', 'press', 'prime', 'pro', 'prod',
    'productions', 'prof', 'progressive', 'promo', 'properties', 'property',
    'protection', 'pru', 'prudential', 'ps', 'pt', 'pub', 'pw', 'pwc', 'py', 'qa',
    'qpon', 'quebec', 'quest', 'racing', 'radio', 're', 'read', 'realestate',
    'realtor', 'realty', 'recipes', 'red', 'redstone', 'redumbrella', 'rehab',
    'reise', 'reisen', 'reit', 'reliance', 'ren', 'rent', 'rentals', 'repair',
    'report', 'republican', 'rest', 'restaurant', 'review', 'reviews', 'rexroth',
    'rich', 'richardli', 'ricoh', 'ril', 'rio', 'rip', 'ro', 'rocher', 'rocks',
    'rodeo', 'rogers', 'room', 'rs', 'rsvp', 'ru', 'rugby', 'ruhr', 'run', 'rw',
    'rwe', 'ryukyu', 'sa', 'saarland', 'safe', 'safety', 'sakura', 'sale', 'salon',
    'samsclub', 'samsung', 'sandvik', 'sandvikcoromant', 'sanofi', 'sap', 'sarl',
    'sas', 'save', 'saxo', 'sb', 'sbi', 'sbs', 'sc', 'sca', 'scb', 'schaeffler',
    'schmidt', 'scholarships', 'school', 'schule', 'schwarz', 'science', 'scot',
    'sd', 'se', 'search', 'seat', 'secure', 'security', 'seek', 'select', 'sener',
    'services', 'ses', 'seven', 'sew', 'sex', 'sexy', 'sfr', 'sg', 'sh',
    'shangrila', 'sharp', 'shaw', 'shell', 'shia', 'shiksha', 'shoes', 'shop',
    'shopping', 'shouji', 'show', 'showtime', 'si', 'silk', 'sina', 'singles',
    'site', 'sj', 'sk', 'ski', 'skin', 'sky', 'skype', 'sl', 'sling', 'sm',
    'smart', 'smile', 'sn', 'sncf', 'so', 'soccer', 'social', 'softbank',
    'software', 'sohu', 'solar', 'solutions', 'song', 'sony', 'soy', 'spa',
    'space', 'sport', 'spot', 'sr', 'srl', 'ss', 'st', 'stada', 'staples', 'star',
    'statebank', 'statefarm', 'stc', 'stcgroup', 'stockholm', 'storage', 'store',
    'stream', 'studio', 'study', 'style', 'su', 'sucks', 'supplies', 'supply',
    'support', 'surf', 'surgery', 'suzuki', 'sv', 'swatch', 'swiss', 'sx', 'sy',
    'sydney', 'systems', 'sz', 'tab', 'taipei', 'talk', 'taobao', 'target',
    'tatamotors', 'tatar', 'tattoo', 'tax', 'taxi', 'tc', 'tci', 'td', 'tdk',
    'team', 'tech', 'technology', 'tel', 'temasek', 'tennis', 'teva', 'tf', 'tg',
    'th', 'thd', 'theater', 'theatre', 'tiaa', 'tickets', 'tienda', 'tiffany',
    'tips', 'tires', 'tirol', 'tj', 'tjmaxx', 'tjx', 'tk', 'tkmaxx', 'tl', 'tm',
    'tmall', 'tn', 'to', 'today', 'tokyo', 'tools', 'top', 'toray', 'toshiba',
    'total', 'tours', 'town', 'toyota', 'toys', 'tr', 'trade', 'trading',
    'training', 'travel', 'travelchannel', 'travelers', 'travelersinsurance',
    'trust', 'trv', 'tt', 'tube', 'tui', 'tunes', 'tushu', 'tv', 'tvs', 'tw', 'tz',
    'ua', 'ubank', 'ubs', 'ug', 'uk', 'unicom', 'university', 'uno', 'uol', 'ups',
    'us', 'uy', 'uz', 'va', 'vacations', 'vana', 'vanguard', 'vc', 've', 'vegas',
    'ventures', 'verisign', 'versicherung', 'vet', 'vg', 'vi', 'viajes', 'video',
    'vig', 'viking', 'villas', 'vin', 'vip', 'virgin', 'visa', 'vision', 'viva',
    'vivo', 'vlaanderen', 'vn', 'vodka', 'volkswagen', 'volvo', 'vote', 'voting',
    'voto', 'voyage', 'vu', 'vuelos', 'wales', 'walmart', 'walter', 'wang',
    'wanggou', 'watch', 'watches', 'weather', 'weatherchannel', 'webcam', 'weber',
    'website', 'wed', 'wedding', 'weibo', 'weir', 'wf', 'whoswho', 'wien', 'wiki',
    'williamhill', 'win', 'windows', 'wine', 'winners', 'wme', 'wolterskluwer',
    'woodside', 'work', 'works', 'world', 'wow', 'ws', 'wtc', 'wtf', 'xbox',
    'xerox', 'xfinity', 'xihuan', 'xin', 'xn--11b4c3d', 'xn--1ck2e1b',
    'xn--1qqw23a', 'xn--2scrj9c', 'xn--30rr7y', 'xn--3bst00m', 'xn--3ds443g',
    'xn--3e0b707e', 'xn--3hcrj9c', 'xn--3pxu8k', 'xn--42c2d9a', 'xn--45br5cyl',
    'xn--45brj9c', 'xn--45q11c', 'xn--4dbrk0ce', 'xn--4gbrim', 'xn--54b7fta0cc',
    'xn--55qw42g', 'xn--55qx5d', 'xn--5su34j936bgsg', 'xn--5tzm5g', 'xn--6frz82g',
    'xn--6qq986b3xl', 'xn--80adxhks', 'xn--80ao21a', 'xn--80aqecdr1a',
    'xn--80asehdb', 'xn--80aswg', 'xn--8y0a063a', 'xn--90a3ac', 'xn--90ae',
    'xn--90ais', 'xn--9dbq2a', 'xn--9et52u', 'xn--9krt00a', 'xn--b4w605ferd',
    'xn--bck1b9a5dre4c', 'xn--c1avg', 'xn--c2br7g', 'xn--cck2b3b', 'xn--cckwcxetd',
    'xn--cg4bki', 'xn--clchc0ea0b2g2a9gcd', 'xn--czr694b', 'xn--czrs0t',
    'xn--czru2d', 'xn--d1acj3b', 'xn--d1alf', 'xn--e1a4c', 'xn--eckvdtc9d',
    'xn--efvy88h', 'xn--fct429k', 'xn--fhbei', 'xn--fiq228c5hs', 'xn--fiq64b',
    'xn--fiqs8s', 'xn--fiqz9s', 'xn--fjq720a', 'xn--flw351e', 'xn--fpcrj9c3d',
    'xn--fzc2c9e2c', 'xn--fzys8d69uvgm', 'xn--g2xx48c', 'xn--gckr3f0f',
    'xn--gecrj9c', 'xn--gk3at1e', 'xn--h2breg3eve', 'xn--h2brj9c', 'xn--h2brj9c8c',
    'xn--hxt814e', 'xn--i1b6b1a6a2e', 'xn--imr513n', 'xn--io0a7i', 'xn--j1aef',
    'xn--j1amh', 'xn--j6w193g', 'xn--jlq480n2rg', 'xn--jlq61u9w7b', 'xn--jvr189m',
    'xn--kcrx77d1x4a', 'xn--kprw13d', 'xn--kpry57d', 'xn--kput3i', 'xn--l1acc',
    'xn--lgbbat1ad8j', 'xn--mgb9awbf', 'xn--mgba3a3ejt', 'xn--mgba3a4f16a',
    'xn--mgba7c0bbn0a', 'xn--mgbaakc7dvf', 'xn--mgbaam7a8h', 'xn--mgbab2bd',
    'xn--mgbah1a3hjkrd', 'xn--mgbai9azgqp6j', 'xn--mgbayh7gpa', 'xn--mgbbh1a',
    'xn--mgbbh1a71e', 'xn--mgbc0a9azcg', 'xn--mgbca7dzdo', 'xn--mgbcpq6gpa1a',
    'xn--mgberp4a5d4ar', 'xn--mgbgu82a', 'xn--mgbi4ecexp', 'xn--mgbpl2fh',
    'xn--mgbt3dhd', 'xn--mgbtx2b', 'xn--mgbx4cd0ab', 'xn--mix891f', 'xn--mk1bu44c',
    'xn--mxtq1m', 'xn--ngbc5azd', 'xn--ngbe9e0a', 'xn--ngbrx', 'xn--node',
    'xn--nqv7f', 'xn--nqv7fs00ema', 'xn--nyqy26a', 'xn--o3cw4h', 'xn--ogbpf8fl',
    'xn--otu796d', 'xn--p1acf', 'xn--p1ai', 'xn--pgbs0dh', 'xn--pssy2u',
    'xn--q7ce6a', 'xn--q9jyb4c', 'xn--qcka1pmc', 'xn--qxa6a', 'xn--qxam',
    'xn--rhqv96g', 'xn--rovu88b', 'xn--rvc1e0am3e', 'xn--s9brj9c', 'xn--ses554g',
    'xn--t60b56a', 'xn--tckwe', 'xn--tiq49xqyj', 'xn--unup4y',
    'xn--vermgensberater-ctb', 'xn--vermgensberatung-pwb', 'xn--vhquv',
    'xn--vuq861b', 'xn--w4r85el8fhu5dnra', 'xn--w4rs40l', 'xn--wgbh1c',
    'xn--wgbl6a', 'xn--xhq521b', 'xn--xkc2al3hye2a', 'xn--xkc2dl3a5ee0h',
    'xn--y9a3aq', 'xn--yfro4i67o', 'xn--ygbi2ammx', 'xn--zfr164b', 'xxx', 'xyz',
    'yachts', 'yahoo', 'yamaxun', 'yandex', 'ye', 'yodobashi', 'yoga', 'yokohama',
    'you', 'youtube', 'yt', 'yun', 'za', 'zappos', 'zara', 'zero', 'zip', 'zm',
    'zone', 'zuerich', 'zw',
);

function check_hostname_fqdn($hostname, $wildcard = false, $dns_strict_tld_check = false)
{

        global $valid_tlds;
        $hostname = trim($hostname, '.');

    if (strlen($hostname) > 255) {
            return trans('The hostname is too long!');
    }

        $hostname_labels = explode('.', $hostname);
        $label_count = count($hostname_labels);

    foreach ($hostname_labels as $hostname_label) {
        if ($wildcard && !isset($first)) {
            if (!preg_match('/^(\*|[a-zA-Z0-9-\/_]+)$/', $hostname_label)) {
                return trans('You have invalid characters in your hostname!');
            }
                $first = 1;
        } else {
            if (!preg_match('/^[a-zA-Z0-9-\/_]+$/', $hostname_label)) {
                return trans('You have invalid characters in your hostname!');
            }
        }
        if ($hostname_label[0] == '-' || substr($hostname_label, -1, 1) == '-') {
            return trans('A hostname can not start or end with a dash!');
        }
        if (strlen($hostname_label) < 1 || strlen($hostname_label) > 63) {
            return trans('Given hostname or one of the labels is too short or too long!');
        }
    }

    if ($hostname_labels[$label_count-1] == 'arpa'
        && (substr_count($hostname_labels[0], '/') == 1 xor substr_count($hostname_labels[1], '/') == 1)
    ) {
        if (substr_count($hostname_labels[0], '/') == 1) {
                $array = explode('/', $hostname_labels[0]);
        } else {
                $array = explode('/', $hostname_labels[1]);
        }
        if (count($array) != 2) {
            return trans('Invalid hostname!');
        }
        if (!is_numeric($array[0]) || $array[0] < 0 || $array[0] > 255) {
            return trans('Invalid hostname!');
        }
        if (!is_numeric($array[1]) || $array[1] < 25 || $array[1] > 31) {
            return trans('Invalid hostname!');
        }
    } else {
        if (substr_count($hostname, '/') > 0) {
            return trans('Given hostname has too many slashes!');
        }
    }

    if ($dns_strict_tld_check && !in_array($hostname_labels[$label_count-1], $valid_tlds)) {
           return trans('You are using an invalid top level domain!');
    }

        return false;
}


function update_soa_serial($did)
{
    global $DB;

    $record = $DB->GetRow("SELECT * from records where domain_id = ? and type='SOA'", array($did));

    $soa = explode(' ', $record['content']);

    if ($soa[2] == '0') {
                return true;
    } elseif ($soa[2] == date('Ymd') . '99') {
            return true;
    } else {
            $today = date('Ymd');

            // Determine revision.
        if (strncmp($today, $soa[2], 8) === 0) {
                // Current serial starts with date of today, so we need to update
                // the revision only. To do so, determine current revision first,
                // then update counter.
                $revision = (int) substr($soa[2], -2);
                ++$revision;
        } else {
                // Current serial did not start of today, so it's either an older
                // serial or a serial that does not adhere the recommended syntax
                // of RFC-1912. In either way, set a fresh serial
                $revision = '00';
        }

            $serial = $today . str_pad($revision, 2, '0', STR_PAD_LEFT);

        // Change serial in SOA array.
            $soa[2] = $serial;

            // Build new SOA record content and update the database
            $DB->Execute(
                'UPDATE records SET content = ? WHERE id = ?',
                array(implode(' ', $soa), $record['id'])
            );
    }
}

/*
 * Parses record content (from DB) into separate form fields
 */
function parse_dns_record(&$record)
{
    $record['name'] = substr($record['name'], 0, -(strlen($record['domainname']) + 1));

    switch ($record['type']) {
        case 'A':
        case 'AAAA':
            $record['ipdst'] = $record['content'];
            break;
        case 'ALIAS':
        case 'CNAME':
        case 'ANAME':
            $record['alias'] = $record['name'];
            $record['domain'] = $record['content'];
            break;
        case 'NS':
            $record['ns'] = $record['content'];
            break;
        case 'MX':
            $record['mailserver'] = $record['content'];
            break;
        case 'TXT':
        case 'SPF':
            $record['desc'] = $record['content'];
            break;
        case 'PTR':
            $record['domain'] = $record['content'];
            break;
        case 'SOA':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['ns'] = $cnt[0];
            $record['email'] = $cnt[1];
            $record['serial'] = $cnt[2];
            $record['refresh'] = $cnt[3];
            $record['retry'] = $cnt[4];
            $record['expire'] = $cnt[5];
            $record['minttl'] = $cnt[6];
            break;
        case 'SSHFP':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['algo'] = $cnt[0];
            $record['ftype'] = $cnt[1];
            $record['fingerprint'] = $cnt[2];
            break;
        case 'SRV':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['weight'] = $cnt[0];
            $record['port'] = $cnt[1];
            $record['domain'] = $cnt[2];
            break;
        case 'HINFO':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['cpu'] = $cnt[0];
            $record['os'] = $cnt[1];
            break;
    }
}

/*
 * Validates record data (from html form)
 * Errors are returned by reference in 4th argument
 */
function validate_dns_record(&$record, &$error)
{
    $arpa_records = array('PTR','SOA','NS','TXT','CNAME','MX','SPF','NAPTR','URL','MBOXFW','CURL','SSHFP');

    // domena in-addr.arpa
    if (preg_match('/in-addr\.arpa$/', $record['domainname'])) {
        if (!in_array($record['type'], $arpa_records)) {
            $error['type'] = trans('Wrong record type!');
        }
    }

    if ($error) {
        return;
    }

    if (!in_array($record['type'], array('SOA', 'CNAME')) && !empty($record['name'])) {
        if ($errorname = check_hostname_fqdn($record['name'], true)) {
            $error['name'] = $errorname;
        }
    }

    switch ($record['type']) {
        case 'A':
            if (empty($record['ipdst'])) {
                $error['ipdst'] = trans('Field cannot be empty!');
            } else if (!check_ip($record['ipdst'])) {
                $error['ipdst'] = trans('Invalid IP address!');
            }
            break;
        case 'AAAA':
            if (empty($record['ipdst'])) {
                $error['ipdst'] = trans('Field cannot be empty!');
            } else if (!check_ipv6($record['ipdst'])) {
                $error['ipdst'] = trans('Invalid IP address!');
            }
            break;
        case 'ALIAS':
        case 'ANAME':
            if (strlen($record['alias']) && $errorname = check_hostname_fqdn($record['alias'], true)) {
                $error['alias'] = $errorname;
            }
            break;
        case 'NS':
            if ($errorcontent = check_hostname_fqdn($record['ns'], false, true)) {
                    $error['ns'] = $errorcontent;
            }
            if (preg_match('/in-addr\.arpa$/', $record['domainname'])) {
                if ($errorcontent = check_hostname_fqdn($record['ns'], false, true)) {
                        $error['ns'] = $errorcontent;
                }
            }
            break;
        case 'MX':
            if (empty($record['mailserver'])) {
                $error['mailserver'] = trans('Field cannot be empty!');
            } else if ($errorcontent = check_hostname_fqdn($record['mailserver'], false, true)) {
                                $error['mailserver'] = $errorcontent;
            }

            if (empty($record['prio'])) {
                $error['prio'] = trans('Field cannot be empty!');
            } else if (!preg_match('/^[0-9]+$/', $record['prio'])) {
                $error['prio'] = trans('Invalid format!');
            }
            break;
        case 'CNAME':
            if ($errorname = check_hostname_fqdn($record['alias'], true)) {
                $error['alias'] = $errorname;
            }
            break;
        case 'TXT':
        case 'SPF':
            if (empty($record['desc'])) {
                $error['desc'] = trans('Field cannot be empty!');
            }
            break;
        case 'PTR':
            if ($errorcontent = check_hostname_fqdn($record['domain'], false, true)) {
                $error['domain'] = $errorcontent;
            }
            break;
        case 'SOA':
            foreach (array('serial', 'refresh', 'retry', 'expire', 'minttl') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                } else if (!preg_match('/^[0-9]+$/', $record[$idx])) {
                    $error[$idx] = trans('Invalid format!');
                }
            }
            break;
        case 'SSHFP':
            foreach (array('algo', 'ftype', 'fingerprint') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                } else if ($idx != 'fingerprint' && !preg_match('/^[0-9]+$/', $record[$idx])) {
                    $error[$idx] = trans('Invalid format!');
                }
            }
            break;
        case 'HINFO':
            foreach (array('cpu', 'os') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                }
                // @TODO: RFC1010 data format checking
            }
            break;
        case 'SRV':
            foreach (array('port', 'weight') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                } else if (!preg_match('/^[0-9]+$/', $record[$idx])) {
                    $error[$idx] = trans('Invalid format!');
                }
            }
            break;
        default: // NAPTR
            if (empty($record['content'])) {
                $error['content'] = trans('Field cannot be empty!');
            }
    }

    if ($error) {
        return;
    }

    // set 'name' and 'content', 'prio' fields to write into DB
    switch ($record['type']) {
        case 'A':
        case 'AAAA':
            $record['content'] = $record['ipdst'];
            break;
        case 'NS':
            $record['content'] = $record['ns'];
            break;
        case 'MX':
            $record['content'] = $record['mailserver'];
            break;
        case 'CNAME':
        case 'ALIAS':
        case 'ANAME':
            $record['name'] = $record['alias'];
            $record['content'] = $record['domain'];
            break;
        case 'TXT':
        case 'SPF':
            $record['content'] = $record['desc'];
            break;
        case 'PTR':
            $record['content'] = $record['domain'];
            break;
        case 'SOA':
            $record['name'] = '';
            $record['content'] = $record['ns'].' '.str_replace('@', '.', $record['email'])
                .' '.$record['serial'].' '.$record['refresh'].' '.$record['retry']
                .' '.$record['expire'].' '.$record['minttl'];
            break;
        case 'SSHFP':
            $record['content'] = $record['algo'].' '.$record['ftype'].' '.$record['fingerprint'];
            break;
        case 'HINFO':
            $record['content'] = $record['cpu'].' '.$record['os'];
            break;
        case 'SRV':
            $record['content'] = $record['weight'].' '.$record['port'].' '.$record['domain'];
            break;
    }

    if ($record['type'] != 'MX') {
        $record['prio'] = 0;
    }
}
