CREATE TABLE cl_posting (post_id    numeric, 
                         subject    varchar(255), 
                         body       text, 
                         user_email varchar(100), 
                         postdate   date);

CREATE TABLE my_reply (post_id         numeric,
                       date_replied    date,
                       subject         varchar(255),
                       body            text);

CREATE TABLE trim_urls (post_id           numeric,
                        trim_url          varchar(255),
                        clicked           boolean,
                        os                varchar(50),
                        country           varchar(20),
                        useragent         varchar(255),
                        dateclicked       date,
                        referrer          varchar(128),
                        referrer_url      varchar(1024),
                        click_count       numeric);
                        
CREATE TABLE girl (post_id           numeric,
                   first_name        varchar(40),
                   last_name         varchar(100),
                   phone             varchar(14),
                   email             varchar(100),
                   address           varchar(50),
                   city              varchar(50),
                   state             varchar(2),
                   age               numeric);

CREATE TABLE her_reply (post_id           numeric,
                        date_she_replied  date,
                        subject           varchar(255),
                        body              text,
                        bot_flag          boolean,
                        first_name        varchar(40),
                        last_name         varchar(100));

CREATE TABLE bot_report (post_id numeric,
                         url     text);
