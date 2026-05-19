<?php
// IP identification function (nhận diện IP khi đi qua Ngrok hoặc Cloudflare)
function getUserIP() {
    // Check for Cloudflare's connecting IP header
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    
    // Check for Ngrok's forwarded IP header
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]); // Return the first IP in the list
    }
    
    // Fallback to the remote address
    return $_SERVER['REMOTE_ADDR'];
}