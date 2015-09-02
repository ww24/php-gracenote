Example
=======

Gracenote Rhythm API を使ったサンプルです。

Requirements
------------
* php >= 5.6
* Composer

Preparation
-----------
### Register Gracenote Account
[https://developer.gracenote.com/ja/user/register](https://developer.gracenote.com/ja/user/register)

### Register New Application
[https://developer.gracenote.com/ja/user/me/apps](https://developer.gracenote.com/ja/user/me/apps)

### Create `apikey.json`
```
cp apikey.json.sample apikey.json
```

```
{
	"gracenote_client_id": "YOUR API KEY HERE!"
}
```

How to start
------------
* `composer install`
* `php -S 0.0.0.0:8080`
* open [`http://localhost:8080/client.html`](http://localhost:8080/client.html) on your browser.
