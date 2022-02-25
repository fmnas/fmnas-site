# print-pdf

This Cloud Function converts an uploaded HTML file to PDF, and is used by the adoption application form processor to
create PDF copies of applications.

## Resources

TODO

## Manual deployment

```shell
cd gcp/print-pdf
npm run compile
gcloud functions deploy print-pdf-test --entry-point printPdf
```

## Running locally

The "Run print-pdf on port 50002" IntelliJ run configuration runs `PORT=50002 npm run watch` in the `gcp/print-pdf`
directory.

### Testing

```shell
curl -v -F 'html=@/path/to/in.html' http://localhost:50002 > out.pdf 
```

Or use the print-pdf task in public/tester.php.
