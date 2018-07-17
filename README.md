# Blueprint
Is a site generator that assembles static pages with blocks, collects images
and builds CSS and JS bundles.

## Usage

```
php blueprint.php
```

## Blueprint.json
Is a map of your site.
```
{
  "codepage":"utf-8", <--------- site parameters
  "pages":[ <--------- list of pages
    {
      "file":"index.html", <--------- page data
      "url":"/",
      "head":[ <--------- list of blocks for HTML HEAD
        {
          "block":"_head", <--------- block folder
          "params":{ <--------- block parameters
            "title":"Page title",
            "description":"blabla"
          }
        }
      ],
      "body":[ <--------- list of blocks for HTML BODY

      ]
    },
    {
      ...
    }

  ]
}

```
