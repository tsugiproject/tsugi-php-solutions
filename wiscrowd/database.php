<?php

$DATABASE_UNINSTALL = "drop table if exists {$CFG->dbprefix}solution_wiscrowd";

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}solution_wiscrowd",
"create table {$CFG->dbprefix}solution_wiscrowd (
    link_id     INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    guess       FLOAT,

    CONSTRAINT `{$CFG->dbprefix}solution_wiscrowd_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}solution_wiscrowd_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"));

