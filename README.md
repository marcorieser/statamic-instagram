# Statamic Instagram

> Statamic Instagram lets you fetch data via the Instagram Business API into your Statamic site.

## Features

- Fetch Instagram posts via Meta's [Instagram API](https://developers.facebook.com/docs/instagram-platform)
- Support image manipulation through Glide by proxying the images

## Limitations
- As of now, you are in charge of refreshing your `ACCESS_TOKEN` once it expires

## How to Install

You can install this addon via Composer:

``` bash
composer require marcorieser/statamic-instagram
```

## How to Use

### Installation

- Install the addon
- Follow Meta's [docs](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/create-a-meta-app-with-instagram) on how to create an Access Token
- Publish the addon config by running `php artisan vendor:publish --tag=statamic-instagram-config`
- Add your Access Token to the `account` section in the published config file or via the `Instagram` section in the Control Panel

### Display the feed

There is a `{{ instagram:feed }}` tag, that fetches the media from the API and returns them as an array.

- The `limit` parameter defaults to `12`.
- The `handle` parameter defaults to the first account in the config.

```antlers
{{ instagram:feed limit="12" handle="rickastley" }}
    {{ id }}
    {{ caption }}
    {{ media_type }}
    {{ media_url }}
    {{ permalink }}
    {{ timestamp }}
    {{ thumbnail_url }}
    
    {{ children }}
        {{ id }}
        {{ media_type }}
        {{ media_url }}
        {{ permalink }}
        {{ timestamp }}
    {{ /children }}
{{ /instagram:feed }}
```
### Display a specific media
There is a `{{ instagram:media }}` tag, that fetches just one specific media.

- The `id` parameter is required.
- The `handle` parameter defaults to the first account in the config.

```antlers
{{ instagram:media id="18051623968824939" handle="rickastley" }}
    {{ id }}
    {{ caption }}
    {{ media_type }}
    {{ media_url }}
    {{ permalink }}
    {{ timestamp }}
    {{ thumbnail_url }}
    
    {{ children }}
        {{ id }}
        {{ media_type }}
        {{ media_url }}
        {{ permalink }}
        {{ timestamp }}
    {{ /children }}
{{ /instagram:feed }}
```

### Manipulate an image
Right now, Glide does not support urls with query params. There is an [open PR](https://github.com/statamic/cms/pull/11003) for that. Until that gets merged, you can proxy the url with the `ig_proxy` modifier like that:
```antlers
{{ instagram:feed }}
    <img src="{{ glide src="{ media_url | ig_proxy}" width="500" }}">
{{ /instagram:feed }}
```

### Fetching Child Media
By default, the addon does not fetch child media for e.g., Albums since that requires additional requests to the API. Therefore `children` is `null`. In case you need to child media, you can enable `include_child_posts` in the addon config. 
