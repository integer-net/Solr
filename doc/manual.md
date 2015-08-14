IntegerNet_Solr
===============
Users / Developers Manual

About
-----
IntegerNet_Solr is a Magento 1.x module which creates a better search experience using Apache Solr as its Engine. 
Its main features are an autosuggest window with product and keyword suggestions based on what is being entered in
the search bar, plus better search results regarding quality and speed.

Features
--------
#### General
- Correction of spelling, fuzzy search
- Displays exact search results first and results to similar search words after that
- Supports multi store functionality of Magento completely
- Can use one Solr core for several Magento store views or seperate cores 
- Can use separate Solr cores for indexing only and swap cores after that
- Allows logging of all Solr requests
- Checks connection and configuration of solr server

#### Autosuggest window
- Appears after the first letters have been typed into the search form
- Displays product suggestions, category suggestions, attribute suggestions and keyword suggestions
- Number of suggestions of each type is configurable in the Magento backend
- Attributes to display can be defined in configuration
- Can skip Magento instantiation and use PHP only for faster results

#### Search results
- Provides all features from Magento default, like sorting, pagination and filters
- Takes search results from Solr for better speed and quality
- Prerenders product HTML blocks for faster rendering
- Allows to configure the price filter steps
- Automatic update of Solr index on create/edit/delete of products

#### Modification of search results
- Modify "fuzzyness"
- Allows boosting of products and attributes
- Provides events for modifying indexing process and search requests

Requirements
------------
- **Magento Community Edition** 1.6 to 1.9 or **Magento Enterprise Edition** 1.11 to 1.14
- **Solr** 4.x or 5.x
- **PHP** 5.3 to 5.5 (5.5 recommended)

Installation
------------
1. Install **Solr** and create at least one working **core**
2. Copy the files from the `solr_conf` dir of the module repository to the `conf` dir of your Solr core
3. Reload the Solr core (or all of Solr)
4. (If activated: deactivate the Magento compiler)
5. Copy the files and dirs from the `src` directory of the module repository into your Magento installation. 
If you are using **modman** and/or **composer** you can find a `modman` file and a `composer.json` in the root directory.
6. Clear the Magento cache
7. (Recompile and reactivate the Magento compiler - it is not recommended to use the compiler mode of Magento, 
independant of the IntegerNet_Solr module)
8. Go to the Magento backend, go to `System -> Configuration -> Solr`.
9. Enter the Solr access data and configure the module (see below)
10. Click **Save Config**. The connection to the Solr server will automatically be tested. You'll get a success or
error message about that.
11. If you are using the Magento Enterprise Edition, you have to switch off the integrated Solr engine by switching 
`System -> Configuration -> Catalog -> Catalog Search -> Search Engine` to `MySql Fulltext`.
12. Reindex the integernet_solr index. We recommend doing this via shell. Go to the `shell` dir and call
`php -f indexer.php -- --reindex integernet_solr`
13. Try typing a few letters in the search box on the frontend. A box with product and keyword suggestions should appear.
 
Technical workflow
------------------

### Indexing
For each product and store view combination, a Solr document is created on the Solr server. This happens through
the Magento indexing mechanism which allows to react on every product change. You can either have a full reindex
process which processes all products efficiently (in batch of 1000 products each, configurable) or a partial reindex.
A partial reindex will happen if any product is created, modified or deleted and will recreate the corresponding
documents in the Solr server for the affected products only so the Solr index is always up to date.

The data which is stored on Solr contains the following information:

- Product ID
- Store ID
- Category IDs
- Contents of all product attributes which are marked as "searchable" in Magento
- Generated HTML for autosuggest window, containing the defined data and layout (i.e. name, price, image, ...)
- If configured: Generated HTML for results page, once for grid mode and once for list mode
- IDs of all options of filterable attributes for the layered navigation

If you are using the full reindex regularily, we recommend using the **swap** functionality. You can configure the 
module to use a different solr core for indexing and swap cores after that (`System -> Configuration -> Solr -> 
Indexing -> Swap Cores after Full Reindex`).  

### Autosuggest
When using the autosuggest functionality, there will be an AJAX call whenever a customer has typed the first few letters
into the search field on the frontend. The AJAX response will be the HTML of the Autosuggest window, including product
data, keyword suggestions, matching categories and/or attributes. The target of the AJAX call will differ depending on 
the configuration setting `System -> Configuration -> Solr -> Autosuggest Box -> Method to retrieve autosuggest information`

#### Magento Controller
This is the basic method which uses Magento methods only, as does the MySQL default or the Solr functionality of the
Magento Enterprise Edition. It's the slowest but the most flexible. It's intended as a fallback if the other methods
shouldn't be working due to whatever reason.

#### Magento with separate PHP file
This will call a separate PHP file `autosuggest-mage.php` in the Magento root dir directly. It skips the routing
process of Magento and thus will deliver the contents faster. Still, all Magento functionality should be working.
We haven't found a disadvantage of this method yet, except its speed (see below).

#### PHP without Magento instantiation
This will call a different PHP file `autosuggest.php` in the Magento root dir directly. It doesn't use most of the 
Magento functionality and is a lot faster in most environments. As it doesn't do any database calls, all the data
which is needed for the autosuggest window will have to come either from Solr directly or from a text file. The 
module automatically generates text files which contain the information used for the default autosuggest window:

- The Solr configuration
- Some additional configuration values
- All category data (names, IDs and URLs)
- All attribute data which is configured to be used in autosuggest (option names, IDs and URLs)
- Some additional information like the Store Base URL or the filename of the template file (see below)
- A copy of the `template/integernet/solr/result/autosuggest.phtml` file which is used in your theme. It has all the
translation text already translated.

The information is stored in `var/integernet_solr/store_x/config.txt` (as serialized array) and 
`var/integernet_solr/store_x/autosuggest.phtml`. These files will be automatically recreated in any of the following 
events:

- AJAX call on the frontend occurs while the file `var/integernet_solr/store_x/config.txt` doesn't exist.
- The configuration of the Solr module is changed.
- Cache is cleared.

So if you want to force recreating that information, trigger any of the above events.

Note that you won't have all Magento functionality available if you are using this method. Please try to stick to
those methods used in `app/design/frontend/base/default/template/integernet/solr/autosuggest.phtml`. For example, you 
cannot include static blocks or other external information without further modification.

Configuration
-------------

--- Will follow soon ---

Template adjustments
--------------------

If you are using a non-standard template, probably some adjustments need to be made. The template of the autosuggest box
and the results page is defined in `app/design/frontend/base/default/template/integernet/solr/` (PHTML files) and 
`skin/frontend/base/default/integernet/solr/` for the CSS file which is included into every page. Copy the files
to your own theme directory (same directory and file name) and adjust them there.

### Results page
Most probably you already have a template for the search results page. Usually it can be found at 
`template/catalog/product/list.phtml` in your theme directory. To generate the according content for the PHTML files
of the IntegerNet_Solr module, you have to split the content of your file into three parts.

- The parts inside `<li class="item...">` go to `template/integernet/solr/result/list/item.phtml` and   
`template/integernet/solr/result/grid/item.phtml` respectively.
- The remainder goes to `template/integernet/solr/result.phtml`. The previously cut out part must be replaced 
with the following code:

    <?php echo $this
        ->getChild('item')
        ->setProduct($_product)
        ->setListType('list')
        ->toHtml() ?>

Replace `list` with `grid` depending on the part you are replacing.
You should switch off the configuration option `Search Results -> Use HTML from Solr Index` while modifying the 
template files. If you have this option activated, you have to do a full reindex after activating / changing
a list or grid template file.

### Autosuggest page
You can copy and modify the `template/integernet/solr/autosuggest.phtml` and `template/integernet/solr/autosuggest/item.phtml`
files to modify the appearance of the autosuggest window. Attention: as the generated HTML for each product is stored
in the Solr index, you'll have to reindex after you made changes to the `template/integernet/solr/autosuggest/item.phtml`
file.

Pay attention: as the autosuggest functionality isn't delivered by Magento but by a raw PHP version in order to 
improve performance, you cannot use all Magento functions in your `template/integernet/solr/result/autosuggest.phtml`. 
Try to stick to the functions which are used in 
`app/design/frontend/base/default/template/integernet/solr/result/autosuggest.phtml`. As the HTML is generated by 
Magento instead, you can use all Magento functions in your `template/integernet/solr/result/autosuggest.phtml`.

If you aren't using product, category, attribute or keyword suggestions on your autosuggest page, please switch them
off in configuration as well as this will improve the performance.

Possible Problems and their solutions
-------------------------------------
1. **Rewrite conflicts with modules which affect the layered navigation**    
    You can't avoid this. But you can resolve the conflicts. You can see how we resolved such a conflict with one
    of those modules in the file `app/code/community/IntegerNet/Solr/Model/Resource/Catalog/Layer/Filter/Price.php`.
    
2. **Saving products in the backend takes a long time**    
    This may happen if you have many store views. We recommend switching the indexing mode of the `integernet_solr` index
    to "Manually" and do a full reindex at night via cronjob if possible.

3. **Product information on the results page should be different for different customer groups, but is the same for all**    
    Turn off `Search Results -> Use HTML from Solr Index` in this case so the product HTML will be regenerated at every call. 
    Please not that this will affect the performance of the search result page. 
    
4. **Product information on the autosuggest window should be different for different customer groups, but is the same for all**
    As the product HTML will always be stored in the Solr index, this is impossible. Try to modify the HTML in 
    `template/integernet/solr/autosuggest/item.phtml` so it doesn't contain customer specific information any more 
    (e.g. prices).