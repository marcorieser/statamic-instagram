# Statamic Instagram Business API

> Statamic Instagram Business API lets you fetch data via the Instagram Business API into your Statamic site.

## Features

- Fetch Instagram posts via Meta's [Instagram Business API](https://developers.facebook.com/docs/instagram-platform)
- Support image manipulation through Glide by proxying the images
- Auto refreshing of Access Tokens

## How to Install

You can install this addon via Composer:

``` bash
composer require marcorieser/statamic-instagram
```

## How to Use

### Installation

- Install the addon
- Publish the addon config by running `php artisan vendor:publish --tag=statamic-instagram-config`
- Add your Access Token to the `account` section in the published config file. If you do not have a token, follow the instructions below.

### Creating a Meta App / an Access Token

Create an Access Token for the API with these steps:

- Login with your Instagram credentials at https://developers.facebook.com
- Create a new app (choose `Other` as use case and `Business` as the app type)
- Add `Instagram` as a product to your app
- Link your instagram account at `1. Generate access tokens` in the `API setup with Instagram login` section of your app
- Generate a token and add it to the config in Statamic 

Further information on that topic in Meta's [docs](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/create-a-meta-app-with-instagram).

### Display the feed

There is a `{{ instagram:feed }}` tag, that fetches the media from the API and returns them as an array.

- The `limit` parameter defaults to `12`.
- The `handle` parameter defaults to the first account in the config.

```antlers
{{ instagram:feed limit="12" handle="rickastley" }}
    {{ id }}
    {{ caption }}
    {{ comments_count }}
    {{ is_shared_to_feed }}
    {{ like_count }}
    {{ media_product_type }}
    {{ media_type }}
    {{ media_url }}
    {{ permalink }}
    {{ thumbnail_url }}
    {{ timestamp }}
    {{ username }}
    
    {{ children }}
        {{ id }}
        {{ comments_count }}
        {{ like_count }}
        {{ media_product_type }}
        {{ media_type }}
        {{ media_url }}
        {{ permalink }}
        {{ thumbnail_url }}
        {{ timestamp }}
        {{ username }}
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
    {{ comments_count }}
    {{ is_shared_to_feed }}
    {{ like_count }}
    {{ media_product_type }}
    {{ media_type }}
    {{ media_url }}
    {{ permalink }}
    {{ thumbnail_url }}
    {{ timestamp }}
    {{ username }}

    {{ children }}
        {{ id }}
        {{ comments_count }}
        {{ like_count }}
        {{ media_product_type }}
        {{ media_type }}
        {{ media_url }}
        {{ permalink }}
        {{ thumbnail_url }}
        {{ timestamp }}
        {{ username }}
    {{ /children }}
{{ /instagram:feed }}
```

### Manipulate an image

Right now, Glide does not support urls with query params. There is an [open PR](https://github.com/statamic/cms/pull/11003) for that. Until that gets merged, you can proxy the url with the `ig_proxy` modifier like that:
```antlers
{{ instagram:feed }}
    {{ glide src="{ media_url | ig_proxy}" width="500" }}
        <img src="{{ url }}" width="{{ width }}" height="{{ height }}">
    {{ /glide }}
{{ /instagram:feed }}
```

### Refreshing Tokens

A long-lived access token is valid for two months.
This would mean that it has to be refreshed manually before it expires. 
When using the `{{ instagram:feed }}` `{{ instagram:media }}` tags,
this is handled automatically for you. 

If you use the API exclusively in PHP, please make sure that you update the token yourself via the scheduler:
```php
Schedule::call(fn() => app(InstagramAPI::class)->refreshAccessToken())->weekly();
```

Please note that the frequency has to be between one day and the mentioned two months until the token expires.


### Child Media (Album)

By default, the addon does not fetch child media for e.g. Albums since that requires additional requests to the API.
Therefore `children` is `null`.
In case you need child media, you can enable `include_child_posts` in the addon config. 

### Using the API in PHP

There is a dedicated `InstagramAPI` class to interact with the API. Its public methods are:

- `cacheKey(...$parts): string`
- `feed(): Collection`
- `getAccount(): Account`
- `getLimit(): int`
- `setLimit(int $limit): static`
- `getUserId(): string`
- `getHandle(): string`
- `setHandle(string $handle): static`
- `media(int $id): ?Media`
- `refreshAccessToken(): bool`

For example, this is how you fetch the feed. This will return you a Collection of `Media` objects.

```php
 return app(\MarcoRieser\StatamicInstagram\InstagramAPI::class)
    ->setLimit(12)
    ->setHandle('rickastley')
    ->feed();
```

## License

Statamic Instagram Business API is paid software with an open-source codebase. 
If you want to use it, you’ll need to buy a license from the Statamic Marketplace. 
The license is valid for only one project. Statamic itself is commercial software and has its own license.
