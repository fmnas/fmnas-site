# minify-html

This Cloud Function minifies an uploaded static HTML file and inlines stylesheets. This cannot be used for interactive
pages; it is used by the application form processor to minify HTML emails before sending.

Currently, @import rules are removed rather than inlined.

## Resources

TODO

## Manual deployment

```shell
cd gcp/minify-html
npm run compile
gcloud functions deploy minify-html-test --entry-point minify
```

## Running locally

The "Run minify-html on port 50003" IntelliJ run configuration runs `PORT=50003 npm run watch` in the `gcp/minify-html`
directory.

### Testing

```shell
curl -v -F 'html=@/path/to/in.html' http://localhost:50003 > out.html 
```

Or use the minify-html task in public/tester.php.
