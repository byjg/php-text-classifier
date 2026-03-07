-- @description: Create wordlist table
CREATE TABLE tc_wordlist (
    token VARCHAR(255) NOT NULL,
    count_ham INTEGER DEFAULT NULL,
    count_spam INTEGER DEFAULT NULL,
    PRIMARY KEY (token)
);

INSERT INTO tc_wordlist (token, count_ham) VALUES ('tc*dbversion', 3);
INSERT INTO tc_wordlist (token, count_ham, count_spam) VALUES ('tc*texts', 0, 0);
