$server = Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "standalone-minimal.php" -WorkingDirectory "D:\MyPerk-New\myperk-backend" -WindowStyle Hidden -PassThru
Start-Sleep 3

# Test GET customer/cards with customer_id
try {
    $resp = Invoke-WebRequest -Uri "http://localhost:8080/myperk/v1/customer/cards?customer_id=3" -Method GET
    Write-Host "=== GET customer/cards?customer_id=3 ==="
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $err = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "Error Body: $err"
    }
}

# Test POST customer/stamp-request
try {
    $resp = Invoke-WebRequest -Uri "http://localhost:8080/myperk/v1/customer/stamp-request" -Method POST -ContentType "application/json" -Body '{"business_id":1,"branch_id":1,"phone":"+1234567891","qr_hash":"TC001"}'
    Write-Host "=== POST customer/stamp-request ==="
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $err = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "Error Body: $err"
    }
}

# Test GET staff/requests
try {
    $resp = Invoke-WebRequest -Uri "http://localhost:8080/myperk/v1/staff/requests" -Method GET
    Write-Host "=== GET staff/requests ==="
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $err = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "Error Body: $err"
    }
}

# Test GET business/1/dashboard
try {
    $resp = Invoke-WebRequest -Uri "http://localhost:8080/myperk/v1/business/1/dashboard" -Method GET
    Write-Host "=== GET business/1/dashboard ==="
    Write-Host "Status: $($resp.StatusCode)"
    Write-Host "Body: $($resp.Content)"
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $err = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "Error Body: $err"
    }
}

Stop-Process -Id $server.Id -Force
Start-Sleep 1