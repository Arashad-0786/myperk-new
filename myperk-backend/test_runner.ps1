<?php
$server = null;
try {
    $server = proc_open(
        ['php', '-S', 'localhost:8080', '-t', 'D:\MyPerk-New\myperk-backend'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ],
        $pipes
    );
    Start-Sleep 3
    
    Write-Host "=== TEST 1: GET customer/cards?customer_id=3 ==="
    $resp = Invoke-WebRequest -Uri 'http://localhost:8080/myperk/v1/customer/cards?customer_id=3' -Method GET
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
    
    Write-Host "`n=== TEST 2: POST customer/stamp-request ==="
    $resp = Invoke-WebRequest -Uri 'http://localhost:8080/myperk/v1/customer/stamp-request' -Method POST -ContentType 'application/json' -Body '{"business_id":1,"branch_id":1,"phone":"+1234567891","qr_hash":"TC001"}'
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
    
    Write-Host "`n=== TEST 3: GET staff/requests ==="
    $resp = Invoke-WebRequest -Uri 'http://localhost:8080/myperk/v1/staff/requests' -Method GET
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
    
    Write-Host "`n=== TEST 4: GET business/1/dashboard ==="
    $resp = Invoke-WebRequest -Uri 'http://localhost:8080/myperk/v1/business/1/dashboard' -Method GET
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
    
} finally {
    if ($server) {
        proc_close($server)
    }
    Start-Sleep 1
}