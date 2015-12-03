About
=============

This PHP script allow you to crawl and return all pages of a website.
You can also index data from HTML to a solr core (see configuration).

Requirements
=============

* PHP 5.3>
* PHP curl installled
* java installed (if you want to use Tika)

Installation
=============

* Download and copy files in a special directory. You must execute the script from the root of Yasc
* Create a json configuration file of use one form the "Examples" directory (with your URL to crawl)
* Execute the script

```
php Yasc.php "Examples/config-all.json"
```

OR

```
php Yasc.php  --config="Examples/config-all.json"
```

* Enjoy

Configuration
=============

In "Examples" directory, you have 2 different configuration file:
* the complete [https://github.com/Apen/Yasc/blob/master/Examples/config-all.json]
* the minimal [https://github.com/Apen/Yasc/blob/master/Examples/config-min.json]

All the parameters are explicit but here is an explanation.

**Note**: the crawler support a resume system to continue crawling. If you want to flush the history add "--flush" in the command line

startUrl
-------------
URL to start with
```
"startUrl": "http://www.yoursite.com/"
```

depth
-------------
number of link depth to follow
```
"depth": "0"
```

defaultHost
-------------
default host to complete relative link (if needed)
```
"defaultHost": "www.yoursite.com"
```

urlFilters / allow
-------------
array of regexp to validate the url
```
"urlFilters": {
    "allow": [
        "^http://www.yoursite.com/"
    ]
}
```

urlFilters / disallow
-------------
array of regexp to invalidate the url
```
"urlFilters": {
    "disallow": [
        "^(file|ftp|mailto):",
        "\\.(gif|GIF|jpg|JPG|png|PNG|ico|ICO|css|CSS|sit|SIT|eps|EPS|wmf|WMF|zip|ZIP|ppt|PPT|mpg|MPG|xls|XLS|gz|GZ|rpm|RPM|tgz|TGZ|mov|MOV|exe|EXE|jpeg|JPEG|bmp|BMP|js|JS)$"
    ]
}
```

request / timeout
-------------
timeout of the page recuperation
```
"request": {
    "timeout": 5
}
```

request / useragent
-------------
useragent of the page recuperation
```
"request": {
    "useragent": "Site-ngo Yasc parser"
}
```

request / sleep
-------------
sleeping time in seconds between request
```
"request": {
    "sleep": 5
}
```

request / headers
-------------
list of headers params to send
```
"headers": [
	"xxxk: 1",
	"Cache-Control: no-cache, must-revalidate",
	"Pragma: no-cache, must-revalidate"
]
```

match
-------------
array of regexp string to match on each crawled pages. The result will be saved in a match.txt file
```
"match": [
    "Opens external link in new window",
    "<p>&nbsp;</p>"
]
```

solr server
-------------
configuration of the solr server
```
"solr": {
        "host": "xxxxxxx",
        "port": "8080",
        "path": "/xxx/xxx/"
}
```

solr / javaPath
-------------
path to the java bin
```
"javaPath": "java"
```

solr / tikaPath
-------------
path to the tika jar
```
"tikaPath": "Plugins/Solr/Tika/tika-app-1.4.jar",
```

solr / mapping / regexp
-------------
regexp mapping
Examples:
```
"solrField": "title",
"pregMatch": "<title[^>]*>(.*?)</title>",
"pregReplace": [
    {
        "from": "from",
        "to": "to"
    }
]
```
```
"solrField": "titredeux_stringM",
"pregMatchAll": "<h2[^>]*>(.*?)</h2>",
"pregReplace": [
    {
        "from": "<.*?>",
        "to": ""
    },
    {
        "from": "\\s+",
        "to": " "
    }
],
"implode": " ; "
```
```
"solrField": "keywords",
"pregMatch": "<meta name=\"keywords\" content=\"(.*?)\"",
"explode": ","
```
```
"solrField": "content",
"pregMatch": "<!--TYPO3SEARCH_begin-->(.*?)<!--TYPO3SEARCH_end-->",
"pregReplace": [
    {
        "from": "<.*?>",
        "to": ""
    },
    {
        "from": "\\s+",
        "to": " "
    }
]
```

solr / mapping / static
-------------
static mapping (values passed to the config file)
```
{
    "solrField": "type",
    "solrValue": "pages"
},
{
    "solrField": "appKey",
    "solrValue": "yasc"
}
```

solr / mapping / special
-------------
special value compiled by Yasc (URL, URL_RELATIVE, HOST, TITLE, CONTENT)
```
{
    "solrField": "id",
    "crawlerField": "URL"
},
{
    "solrField": "host",
    "crawlerField": "HOST"
},
{
    "solrField": "url",
    "crawlerField": "URL_RELATIVE"
}
```

solr / mappingTika / static
-------------
static mapping (values passed to the config file)
```
 {
    "solrField": "type",
    "solrValue": "file"
},
{
    "solrField": "appKey",
    "solrValue": "yasc"
}
```

solr / mappingTika / special
-------------
special value compiled by Yasc (URL, URL_RELATIVE, HOST, TITLE, CONTENT)
```
{
    "solrField": "id",
    "crawlerField": "URL"
},
{
    "solrField": "host",
    "crawlerField": "HOST"
},
{
    "solrField": "url",
    "crawlerField": "URL"
},
{
    "solrField": "title",
    "crawlerField": "TITLE"
},
{
    "solrField": "content",
    "crawlerField": "CONTENT"
}
```

Example of execution
=============

```
Solr service responding [xxx:8080/xxx/]
---------------------------------------------------------------------------------------------
Depth : 1
Links to crawl : 1
Current memory : 1.53kb
Current URL : http://www.yoursite.com/
Response : content_type=text/html;charset=utf-8 / http_code=200 / parsed=0.43s
Solr : sending doc [TYPO3|Site Internet|Blog - Agence Web Site'nGo.fr]
Solr response : 200 OK / parsed=4ms
---------------------------------------------------------------------------------------------
Depth : 0
Links to crawl : 14
Current memory : 1.56kb
Current URL : http://www.yoursite.com/a-propos/lequipe/
Response : content_type=text/html;charset=utf-8 / http_code=200 / parsed=0.47s
Solr : sending doc [L'équipe - Site'nGo.fr]
Solr response : 200 OK / parsed=4ms
---------------------------------------------------------------------------------------------
Depth : 0
Links to crawl : 13
Current memory : 1.56kb
Current URL : http://www.yoursite.com/a-propos/lequipe/yohann-cerdan/
Response : content_type=text/html;charset=utf-8 / http_code=200 / parsed=0.47s
Solr : sending doc [Yohann CERDAN - Site'nGo.fr]
Solr response : 200 OK / parsed=4ms
---------------------------------------------------------------------------------------------
Depth : 0
Links to crawl : 12
Current memory : 1.56kb
Current URL : http://www.yoursite.com/a-propos/lequipe/thomas-leroy/
Response : content_type=text/html;charset=utf-8 / http_code=200 / parsed=0.44s
Solr : sending doc [Thomas LEROY - Site'nGo.fr]
Solr response : 200 OK / parsed=4ms
---------------------------------------------------------------------------------------------
...
---------------------------------------------------------------------------------------------
Links parsed
Array
(
    [0] => http://www.yoursite.com/
    [1] => http://www.yoursite.com/a-propos/lequipe/
    [2] => http://www.yoursite.com/a-propos/lequipe/thomas-leroy/
    [3] => http://www.yoursite.com/a-propos/lequipe/yohann-cerdan/
    [4] => http://www.yoursite.com/a-propos/nos-developpements/
    [5] => http://www.yoursite.com/a-propos/nos-valeurs/
    [6] => http://www.yoursite.com/contact/
    [7] => http://www.yoursite.com/les-actualites/article/apache-solr-sitengo-persiste-et-signe/
    [8] => http://www.yoursite.com/les-actualites/article/sitengo-premiere-entreprise-francaise-psl-typo3/
    [9] => http://www.yoursite.com/les-actualites/article/t3uni12-luniversite-dete-typo3-2012/
    [10] => http://www.yoursite.com/nos-solutions/blog/wordpress/
    [11] => http://www.yoursite.com/nos-solutions/expertise/
    [12] => http://www.yoursite.com/nos-solutions/gestion-de-contenu/typo3/
    [13] => http://www.yoursite.com/references/
    [14] => http://www.yoursite.com/rss.xml
)
---------------------------------------------------------------------------------------------
Parsed in 25.74s
---------------------------------------------------------------------------------------------
```