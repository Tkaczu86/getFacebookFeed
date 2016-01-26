# getFacebookFeed
Training facebook api graph v2.5 - Get all feed in facebook with photo from site who your want

## Instruction USE

1. Save parameters to connect own database
2. Create 3 tables in database from sql code below:

    ```CREATE TABLE IF NOT EXISTS `facebook_api` (
      `id` tinyint(1) unsigned NOT NULL,
      `api_key` varchar(250) NOT NULL,
      `api_secret` varchar(250) NOT NULL,
      `api_page` varchar(250) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;```

    ```CREATE TABLE IF NOT EXISTS `facebook_feed` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
      `type` varchar(16) NOT NULL,
      `name` varchar(250) DEFAULT NULL,
      `message` varchar(250) DEFAULT NULL,
      `link` varchar(250) DEFAULT NULL,
      `link_to_feed` varchar(250) DEFAULT NULL,
      `source` varchar(250) DEFAULT NULL,
      `description` varchar(250) DEFAULT NULL,
      `created_time` datetime NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;```

    ```CREATE TABLE IF NOT EXISTS `facebook_feed_photo` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `feed_id` int(10) unsigned NOT NULL,
      `picture_size` int(10) unsigned NOT NULL,
      `picture_name` varchar(64) NOT NULL,
      `picture_description` varchar(250) NOT NULL,
      `date_added` datetime NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;```

3. Add row to database with settings facebook "API KEY, API SECRET" and your page name who want to download feed
4. Change chmod on 755 in folder img/feed

Done. :-)

    Start script in your domain -> example: http://www.yourdomain.com/getfeeddata.php