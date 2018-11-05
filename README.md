Bus Backend
===========

This system is designed to be able to read the TransXchange files within the
Traveline National Data Set (TNDS) and import them into a MySQL database for
easy querying, alongside the NaPTAN dataset. It then exposes them via a simple
REST-style interface.

./var needs to be populated with the data in raw form.

Installing
----------

It basically needs its own directory on a server, and the ./htdocs subdirectory
within will be the web accessible directory. This can be symlinked if needed.
To clarify: the repo needs to be pulled into a directory *one higher* than
htdocs.

```
mkdir [directory]
cd [directory]
git clone git@github.com:ads04r/bus-backend.git .
git submodule update --init --recursive
mkdir ./var
```

Then the 'go' script needs to be run weekly from cron.

```
0       3       *       *       3       /var/wwwsites/southampton.ac.uk/api.bus/bin/go
```


Further documentation will follow.

Links
-----
* Traveline National Data Set
  http://www.traveline.info/about-traveline/developer-area/
* National Public Transport Access Nodes (NaPTAN)
  http://data.gov.uk/dataset/naptan
* Food Standards Agency - Food Hygiene Ratings
  http://ratings.food.gov.uk/open-data/en-GB
