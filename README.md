# Web service discovery

Allows to integrate Moodle web services with external systems including AI agents.

Authenticates a user with their web service token and returns the list
of available web service protocols and functions in JSON format.

Request example:

```
WSTOKEN=123456789YOURWSTOKEN123456789012
MOODLEURL=https://your.moodle.site

curl "${MOODLEURL}/admin/tool/wsdiscovery/moodle.php" -H "Authorization: Bearer ${WSTOKEN}"
```