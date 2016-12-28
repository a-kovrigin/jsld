# JSLD module for Drupal

JSLD is simple API for add Json-LD support to your site.

Module doesn't do nothing without you, it's just help you to organize your Json-LD data on site.

For more information you can check **jsld.api.php** file.

## Hooks

### hook_jsld_info().

This hook help you to define custom callbacks for different cases, also you can strict
this callbacks to specific entity type and bundle.

This hook has group "jsld", so you can create **MYMODULE.jsld.inc** in the root of your module, and this file will be included before calling info hook and callbacks.

**Return values**

An associative array of jsld callbacks. They key is name for callback, nothing else.

* **'callback'**: _(required)_ A function name which will be called.
* **'file'**: _(optional)_ A file that will be included before callback is called.
* **'file_path'**: _(optional)_ The path to directory containing the file from "file" parameter above. By default uses root of the module implementing the hook.
* **'entity'**: _(optional)_ An entity machine name to which you can attach callback. If you fill this, callback will be called only when on page rendered one or more entity of this type. When you set this, all information about entity will be passed to first argument.
* **'entity_limit'**: _(optional)_ An array with limitation for entity. You can limit callback to be executed by specific bundle and\or view mode fo entity. Each value must contain $bundle|$view_mode. You may use * to select all. This parameter is not working without "entity" parameter.
* **'match_path'**: _(optional)_ An array with limitation for paths. You can set the where this callback will be called. Also this parameter respect entity limitation parameters, so you can limit by entity, bundle, view_mode and path together. You can use default drupal placeholders, like `<front>`, `*` and so on.
* **'match_type'**: _(optional)_ Define how match path will be used. `JSLD_MATCH_TYPE_LISTED` (default) - only on "match_path" which listed in array, `JSLD_MATCH_TYPE_UNLISTED` - for all pages which is not match to defined in "match_path".

~~~php
/**
 * Implements hook_jsld_info().
 */
function hook_jsld_info() {
  $items['example1'] = array(
    'callback' => 'mymodule_jsld_example1',
  );

  $items['example2'] = array(
    'callback' => 'mymodule_jsld_example2',
    'file' => 'mymodule.example2.inc',
  );

  $items['example3'] = array(
    'callback' => 'mymodule_jsld_example3',
    'file' => 'mymodule_example3.inc',
    'file_path' => drupal_get_path('module', 'MYMODULE') . "/includes/jsld",
  );

  $items['example4'] = array(
    'callback' => 'mymodule_jsld_example3',
    'entity' => 'node',
  );

  $items['example5'] = array(
    'callback' => 'mymodule_jsld_example5',
    'entity' => 'node',
    'entity_limit' => array('news|teaser', 'article|full', 'page|*'),
  );
  
  $items['example6'] = array(
    'callback' => 'mymodule_jsld_example6',
    'match_path' => array('<front>', 'about', 'about/*'),
  );
  
  $items['example7'] = array(
      'callback' => 'mymodule_jsld_example7',
      // All except frontpage.
      'match_path' => array('<front>'),
      'match_type' => JSLD_MATCH_TYPE_UNLISTED,
    );

  return $items;
}
~~~

### hook_js_info_alter().

This hook help you to alter others info hook definitions.

~~~php
/**
 * Implements hook_js_info_alter().
 */
function hook_jsld_info_alter(&$info) {
  $info['mymodule']['article']['file_path'] = drupal_get_path('module', 'jslc') . "/includes/jsld";
}
~~~


### hook_jsld_alter().

This can help you to midfy ready to render data. This  called just before `json_encode` and putting this on page.

~~~php
/**
 * Implements hook_jsld_alter().
 */
function hook_jsld_alter(&$jsld) {
  $jsld['@context'] = 'http://schema.org';
}
~~~

## Functions

### jsld_push_data()

You can easily add data from every place you like. Use `jsld_push_data()` for this.

## Examples

### Example 1 - Schema.org for testimonials

* Entity type: `node`
* Machine name of bundle: `testimonial`

~~~php
/**
 * Implements hook_jsld_info().
 */
function MYMODULE_jsld_info() {
  $items['testimonials'] = array(
    'callback' => 'MYMODULE_jsld_testimonials',
    'entity' => 'node',
    // We need this schema for all view modes of testimonials.
    'entity_limit' => array('testimonial|*'),
  );

  return $items;
}

/**
 * Testimonial Schema.org.
 */
function MYMODULE_jsld_testimonials($entity_info) {
  global $base_url;
  $wrapper = entity_metadata_wrapper('node', $entity_info['entity']);
  $nid = $wrapper->getIdentifier();
  $body = $wrapper->body->value();

  $jsld = array(
    '@context' => 'http://schema.org',
    '@type' => 'Review',
    'author' => array(
      '@type' => 'Person',
      'name' => $wrapper->field_testimonial_name->value(),
    ),
    'url' => "$base_url/testimonials#testimonial-$nid",
    'datePublished' => date('c', $wrapper->created->value()),
    'description' => $body['safe_value'],
    'inLanguage' => $entity_info['langcode'],
    'itemReviewed' => array(
      '@type' => 'Organization',
      'name' => variable_get('site_name', ''),
      'sameAs' => $base_url,
      'url' => $base_url,

    ),
    'reviewRating' =>  array(
      '@type' => 'Rating',
      'worstRating' => 1,
      'bestRating' => 5,
      'ratingValue' => 5,
    ),
  );

  return $jsld;
}
~~~

![Testimonial at Google test](http://i.imgur.com/jJzjHhF.png)

### Example 2 - Schema.org product

This examples based on drupal_commerce product display node which referenced to product bundle of commerce_product entity.

~~~php
/**
 * Implements hook_jsld_info().
 */
function MYMODULE_jsld_info() {
  $items['product'] = array(
    'callback' => 'MYMODULE_jsld_product',
    'entity' => 'node',
    'entity_limit' => array('product_display|*'),
  );

  return $items;
}

/**
 * Product Schema.org
 */
function MYMODULE_jsld_product($entity_info) {
  global $base_url;
  $wrapper = entity_metadata_wrapper('node', $entity_info['entity']);
  
  $description = $wrapper->body->value();
  $photo_file = $wrapper->field_products[0]->field_product_photos[0]->value()
  $photo = image_style_url('medium', $photo_file['uri']);
  $price = $wrapper->field_products[0]->commerce_price->value();
  
  $jsld = array(
    '@context' => 'http://schema.org',
    '@type' => 'Product',
    'name' => $wrapper->label(),
    'description' => strip_tags($description['safe_value']),
    'image' => $photo,
    'sku' => $wrapper->field_products[0]->sku->value(),
    'offers' => array(
      '@type' => 'Offer',
      'priceCurrency' => $price['currency_code'],
      'price' => commerce_currency_amount_to_decimal($price['amount'], $price['currency_code']),
    ),
  );
  
  return $jsld;
}
~~~

![Product at Google test](http://i.imgur.com/Nl5XIGR.png)

Example of result in Google search. This page also contains testimonial(s) from above example.

![Product in search](http://i.imgur.com/oc9qI5Y.png)

### Example 3 - Schema.org article

~~~php
/**
 * Implements hook_jsld_info().
 */
function MYMODULE_jsld_info() {
  $items['article'] = array(
    'callback' => 'MYMODULE_jsld_article',
    'entity' => 'node',
    'entity_limit' => array('article|*'),
  );

  return $items;
}

/**
 * Product Schema.org
 */
function MYMODULE_jsld_article($entity_info) {
  global $base_url;
  $wrapper = entity_metadata_wrapper('node', $entity_info['entity']);
  $body = $wrapper->body->value();
  $logo_path = "$base_url/" . drupal_get_path('theme', 'THEME_NAME') . "/logo.png";
  // For better performance try to avoid using getimagesize() for logo, cuz they are static.
  $logo_width = '500px';
  $logo_height = '200px';

  $jsld = array(
    '@context' => 'http://schema.org',
    '@type' => 'Article',
    'name' => $wrapper->label(),
    'headline' => $wrapper->label(),
    'articleBody' => strip_tags($body['safe_value']),
    'description' => strip_tags($body['safe_value']),
    'url' => $wrapper->url->value(),
    'mainEntityOfPage' => $wrapper->url->value(),
    'datePublished' => date('c', $wrapper->created->value()),
    'dateModified' => date('c', $wrapper->changed->value()),
    'author' => array(
      '@type' => 'Organization',
      'name' => variable_get('site_name', ''),
      'sameAs' => $base_url,
      'url' => $base_url,
      'logo' => array(
        '@type' => 'ImageObject',
        'url' => $logo_path,
        'width' => $logo_width,
        'height' => $logo_height,
      ),
    ),
    'publisher' => array(
      '@type' => 'Organization',
      'name' => variable_get('site_name', ''),
      'sameAs' => $base_url,
      'url' => $base_url,
      'logo' => array(
        '@type' => 'ImageObject',
        'url' => $logo_path,
        'width' => $logo_width,
        'height' => $logo_height,
      ),
    ),
  );

  if ($promo_image = $wrapper->field_article_promo->value()) {
    $jsld['image'] = array(
      '@type' => 'ImageObject',
      'url' => image_style_url('article_teaser_promo', $promo_image['uri']),
      // We use image style above, which have static width and height.
      'width' => '320px',
      'height' => '150px',
    );
  }
  
  return $jsld;
}
~~~

![Article at Google test](http://i.imgur.com/BvUaJUs.png)

## Copyright

Created by Niklan. This module is custom and have no project on drupal.org. So for all updates and info go to [Github project page](https://github.com/Niklan/jsld).
