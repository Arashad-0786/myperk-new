<?php
// Minimal test PHP CLI server file
// Just test that the server can serve a simple endpoint

http_response_code(200);
echo 'Hello, World!';