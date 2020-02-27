<html>
    <head>
        <title>PHP Azure Managed Identity // KeyVault // Postgres Sample</title>
    </head>
    <body>
        <h2>PHP Azure Managed Identity // KeyVault // Postgres Sample</h2>

        <?php 
            // Set Up Curl Headers
            $curlHeaders = array(
                'Metadata: true',
                'Content-Type: application/json'
            );

            // Get MSI Endpoint from Environment for App Service or use default for VM
            if (getenv('MSI_ENDPOINT') !== false) {
                $msiEndpoint = getenv('MSI_ENDPOINT');
                $msiApiVersion = '2017-09-01';
                $curlHeaders[] = 'secret: '.getenv('MSI_SECRET'); // Add MSI_SECRET to Headers
            } else {
                $msiEndpoint = 'http://169.254.169.254/metadata/identity/oauth2/token';
                $msiApiVersion = '2018-02-01';
            }
            $msiUri = $msiEndpoint."?api-version=$msiApiVersion&resource=https%3A%2F%2Fvault.azure.net";
        ?>

        <hr><h3>Retrieve Token for KeyVault Access</h3>
        <div>msiUri = <?= $msiUri ?> </div>

        <?php 
            $cUrl = curl_init($msiUri);
            curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cUrl, CURLOPT_HTTPHEADER, $curlHeaders);
            $response =  curl_exec($cUrl);
            curl_close($cUrl);
            $parsed = json_decode($response);

            $kvToken = $parsed->access_token;
            $kvTokenExpiration = $parsed->expires_on;
            $kvTokenExpirationUnitTime = strtotime($kvTokenExpiration); // UNIX TIME for comparisons
        ?>

        <div> Token:       <?= substr($kvToken,0,6).'.............' ?></div>
        <div> Valid until: <?= $kvTokenExpiration; ?> </div>

        <hr><h3>Retrieve Config from KeyVault</h3>

        <?php 
            //CONFIG SECRET BLOB EXAMPLE: 
            //  {"pgserver":"yourdbservername.postgres.database.azure.com",
            //   "pgdbname":"postgres",
            //   "pguser":"padmin@yourdbservername",
            //   "pgpassword":"REDACTED"}
            
            $kvName = getenv('KV_NAME') !== false ? getenv('KV_NAME') : 'mykeyvaultname';
            $configSecretName = getenv('KV_SECRETNAME') !== false ? getenv('KV_SECRETNAME') : 'myconfigsecretname';
            $kvEndpoint = "https://$kvName.vault.azure.net/secrets/$configSecretName/?api-version=7.0";
        ?>

        <div> KeyVault Secret URI: <?= $kvEndpoint ?> </div>
        <br/>
        
        <?php
            // Use Managed Identiy bearer token to retrieve config blob from KeyVault
            $cUrl = curl_init($kvEndpoint);
            curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cUrl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$kvToken,
                'Content-Type: application/json'
            ));
            $response =  curl_exec($cUrl);
            curl_close($cUrl);
            $parsed = json_decode($response); // Parse KeyVault response
            $parsedValue = json_decode($parsed->value); // Parse config blob
        ?>

        <div> PGServer: <?= $parsedValue->pgserver ?> </div>
        <div> PGDatabase: <?= $parsedValue->pgdbname ?> </div>
        <div> PGUser: <?= $parsedValue->pguser ?> </div>
        <div> PGPassword: REDACTED </div>

        <hr><h3>Test Connection to PostgreSQL</h3>

        <?php
            $connString = 'host='.$parsedValue->pgserver.' port=5432 dbname='.$parsedValue->pgdbname.' user='.$parsedValue->pguser.' password='.$parsedValue->pgpassword.' sslmode=require';
            $query = 'SELECT datname FROM pg_database;';
        ?>
        <div> ConnectionString: <?= str_replace($parsedValue->pgpassword,'REDACTED',$connString) ?> </div>

        <div> Running query: <?= $query ?> </div>
        <br/>
        <div> Results:</div>
        <?php
            // Connect to database and run query
            $dbConn = pg_connect($connString);
            $dbResult = pg_query($dbConn, $query);
            $i=0;
            // Output first field of each row returned
            while ($row = pg_fetch_row($dbResult)) {
                echo "Database[$i]: $row[0]<br/>";
                $i++;
            }
        ?> 
    </body>
</html>
