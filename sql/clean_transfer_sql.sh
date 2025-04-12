#/bin/bash

# Remove bits from the SQL file which we don't want or need:
cat transfer.sql | sed 's/^.*SET character_set_client =.*//' \
	| sed 's/^.*SET @saved_cs_client     = @@character_set_client.*//' \
	| sed 's/^.*SET character_set_client = @saved_cs_client.*//' \
	| sed 's/^) \(.*\)$/) \1;/' \
	| sed 's/ CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci//' \
	| sed 's/ DEFAULT;//' \
	> transfer_clean.sql

cat transfer_clean.sql | grep -v '^/\*.* SET .*\*/' > transfer.sql

rm -f transfer_clean.sql


