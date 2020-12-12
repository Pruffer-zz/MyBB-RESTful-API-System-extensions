# MyBB-RESTful-API-System-extensions
**Likely not ready for production.** 

Extensions for, and a slightly modified version of [MyBB-RESTful-API-System](https://github.com/mohamedbenjelloun/MyBB-RESTful-API-System).

Outputs only in JSON for now.

Versions of the original APIs modified to use JSON input included as well.

**Makeshift docs:**
  - Every modified API needs the same input as it does in the original plugin, but you need to pass it like this in the request body (You still need to pass the `username` and `password` as HTTP headers):
  ```json
   {
    "subject":"Hello!",
    "content":"This is a test of the 'forum' API.",
    "forumid":"3",
    "ipaddress":"192.168.0.1"
   }
  ```
  - As for the `file` APIs, check the `fileapi.class.php` file - starting at line 36, you can find an array of generally self-explanatory keys you need to access them. Pass these as the request body, too- except for the `upload` api. You'll need to use the `Content-type: multipart/form-data` header there, and pass the same data through a field named `json`. *(If there are any better ways to do this that don't involve headers, please let me know.)*
  ```php
  "exampleApi" => array(
    array("filename","location"), // String keys
    array("overwrite","move") // Bool keys
    // ...
  );
  ```
  - Except for the API mentioned above, use the `Content-type: application/json` header for all APIs.

`restfulapi.php` file modified to add 2 settings for the `file` APIs.
