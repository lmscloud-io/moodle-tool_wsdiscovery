# Web service discovery

This plugin helps integrate Moodle web services with external systems, including AI agents.

It authenticates a user via their web service token and returns a JSON-formatted list of available
web service protocols and functions.

## Example request

```
WSTOKEN=123456789YOURWSTOKEN123456789012
MOODLEURL=https://your.moodle.site

curl "${MOODLEURL}/admin/tool/wsdiscovery/moodle.php" -H "Authorization: Bearer ${WSTOKEN}"
```

## Example response

[example.json](examples/example.json)