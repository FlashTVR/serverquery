# Server Query

This is a very basic game server query framework. It's designed to be easily extendable to accommodate more games. It requires PHP 5.3+.

## Installation

1. Upload the `serverquery/` directory to your website
   - If you are using the cache (default/recommended), make sure the `serverquery/cache/` directory is writable
2. Rename `config.example.php` to `config.php` and edit it with your settings, games, and servers
   - Edit `template.php` and/or `serverquery.css` as needed
3. Include `serverquery/serverquery.php` using `include` or `require` somewhere on your webpage

## Configuration

All configuration settings are in `config.php`. Each option is well documented in a docblock, so refer to those for details.

### Game Configurations

Each game class can accept an array of options specific to that game engine. Each array in `$games` and `$servers` can have a `config` key that is an associative array of options. Options set in `$servers` override options set in `$games`.

Here are the available options and default settings for each included game class:

#### Valve

Option      | Type    | Default | Description
----------- | ------- | ------- | -----------
*hideBots*  | boolean | `true`  | Hide bots from player count and list

#### Minecraft

Option      | Type    | Default | Description
----------- | ------- | ------- | -----------
*useQuery*  | boolean | `false` | Use the Query protocol (server must set `enable-query=true`)
*queryPort* | integer | `25565` | Port used by the Query protocol (server `query.port` property)
*useLegacy* | boolean | `false` | Use the pre-1.7 Server List Ping protocol (overrides `useQuery`)

#### TShock (Terraria server)

Option      | Type    | Default | Description
----------- | ------- | ------- | -----------
*queryPort* | integer | `7878`  | Port used to query the server REST API

#### TeamSpeak 3

Option      | Type    | Default | Description
----------- | ------- | ------- | -----------
*queryPort* | integer | `10011` | Port used by the ServerQuery protocol
*queryUser* | string  |         | Username for the ServerQuery protocol
*queryPass* | string  |         | Password for the ServerQuery protocol

## Cron Mode

With cron mode enabled, the servers must be queried by `cron.php`. For best results, execute the script once every 1 minute.

On linux, this can be achieved by adding a line to your crontab like the one below.
```
* * * * * php /path/to/cron.php > /dev/null 2>&1
```

## License

This project is released under the [MIT License](http://sguidetti.mit-license.org/).

## Changes

- Aug 21, 2015 [v.0.2.0]
   * Uses namespaces (requires PHP 5.3+)
   * Switched to JSON format to store cache data
- Aug 03, 2015 [v.0.1.0]
   * Initial release
