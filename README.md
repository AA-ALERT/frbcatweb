# frbcatweb
Fast Radio Burst (FRB) Catalogue Web tools (both front-end and back-end)

The back-end requires the FRBCatDB web running. The credentials to access the DB
have to be provided in a file called db_secrets.txt located in the root of frbcatweb.

db_secrets.txt must contain the following information:

```
[host]
[user]
[password]
[DB name]
```

 The back-end also requires a file called `setenv.sh`. This sets the environment before running the `run_csv_to_vo_table.py` script. Since the latter script requires astropy, the `setenv.sh` must for sure add astropy to the environment.
