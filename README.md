# MediaWiki Extension RestrictImageDownload

Restrict image file downloads

## Install

To install this extension, add the following to LocalSettings.php.

```PHP
wfLoadExtension("RestrictImageDownload");
```

### Optional settings

- `$wgRestrictImageFiles`
  - image files to be restricted
  - default: `[]`

### Additional settings

Append following mod\_rewrite rules for restricted file to images/.htaccess.

```
RewriteRule ^(0/00/Restrict_image\.png)$ /w/extensions/RestrictImageDownload/wrapper.php/$1 [L]
RewriteRule ^(thumb/0/00/Restrict_image\.png/.*)$ /w/extensions/RestrictImageDownload/wrapper.php/$1 [L]
```

## License

This software is licensed under the [MIT License](LICENSE).

## Authors

- [Yoshihiro Okumura](https://github.com/orrisroot)

