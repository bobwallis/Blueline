<?php
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; ?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
<?php echo $content_for_layout; ?>
	<Image height="64" width="64" type="image/png"><?php echo $site['baseURL']; ?>/icon/64.png</Image>
	<Image height="16" width="16" type="image/x-icon"><?php echo $site['baseURL']; ?>/favicon.ico</Image>
	<Contact>blueline@rsw.me.uk</Contact>
	<AdultContent>false</AdultContent>
	<Language>*</Language>
	<OutputEncoding>UTF-8</OutputEncoding>
	<InputEncoding>UTF-8</InputEncoding>
</OpenSearchDescription>
