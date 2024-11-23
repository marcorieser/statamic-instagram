# Statamic Instagram

> Statamic Instagram lets you fetch data via the Instagram Business API into your Statamic site.

## Features

- Fetch Instagram posts via Meta's [Instagram API](https://developers.facebook.com/docs/instagram-platform)
- Support image manipulation through Glide by proxying the images

## Limitations

- The Instagram feed gets generated on the `user_id` that is linked to the provided `ACCESS TOKEN` 
- As of now, you have to bring your `ACCESS TOKEN` and are in change of refreshing it once it expires

## How to Install

You can install this addon via Composer:

``` bash
composer require marcorieser/statamic-instagram
```

## How to Use

### Installation

- Install the addon
- Follow Meta's [docs](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/create-a-meta-app-with-instagram) on how to create an Access Token
- Add your `STATAMIC_INSTAGRAM_ACCESS_TOKEN` to your `.env`

### Display the feed

There is a `{{ instagram:feed }}` tag, that fetches the posts and returns you an array of data. The `limit` parameter defaults to `12`. 

```antlers
{{ instagram:feed limit="12" }}
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
Right now, Glide does not support urls with query params. There is an [open PR](https://github.com/statamic/cms/pull/11003) for that. Until that gets merged, you can proxy with `{{ instagram:proxy }}` the image like that:
```antlers
{{ instagram:feed }}
    <img src="{{ glide src="{instagram:proxy}" }}">
{{ /instagram:feed }}
```
