# PHP Azure Managed Identity // KeyVault // Postgres Sample

This is a simple sample PHP page that demonstrates the following:
1. Retrieves a bearer token from the either the endpoint listed in the MSI_ENDPOINT environment variable (Azure App Service) or from the default token endpoint (Azure VMs)
2. Uses the bearer token to interact with Azure KeyVault to retrieve a database configuration blob stored as a secret
3. Parses the database configuration blob
4. Uses the parsed data to connect to the Postgres database and list the available databases

## Running the Sample
1. Create an Azure App Service, Azure VM, or other method for hosting the PHP page.
2. Create a Postgres database instance.
3. Create an Azure KeyVault to store the Postgres Config Values.
4. Create a secret in the Azure Key Vault with the following structure:
            ```{"pgserver":"yourdbservername.postgres.database.azure.com",
            "pgdbname":"postgres",
            "pguser":"padmin@yourdbservername",
            "pgpassword":"REDACTED"}```
5. Edit the index.php assignments for [$kvName](index.php#L55) and [$configSecretName](index.php#L56) or modify your hosting setup to populate environment variables KV_NAME and KV_SECRETNAME.  
    - $kvName or KV_NAME should be the name of your KeyVault (do not include ".vault.azure.net").  
    - $configSecretName or KV_SECRETNAME should be the name of the secret where your Postgres config blob is stored.
6. Deploy index.php, then navigate to your hosting endpoint.

[See License](/LICENSE)
