I did this project for This article appeared in 2600: The Hacker Quarterly, Volume 27, Number 2.

Casual Encounters of the Third Kind:
====================================
A Bayesian Classifier for Craigslist
------------------------------------

######By Brian Detweiler


##Introduction

In this article, I scientifically examine the myth of
Craigslist Casual Encounters. The focus has been placed
on w4m (women4men) in the Omaha, Nebraska location.
This research could (and should) be expanded to other
cities, as well as other keywords.

##The Idea

I have long held the belief that sexually frustrated men
everywhere are being taken advantage of. 
Everything from girls asking for free drinks at bars to 
pay websites like AdultFriendFinder.com charging money
for finding women to hook up with. Craigslist, however, is
free, minimalistically designed, and used by millions of
people around the globe. It seems like the perfect way
for someone to get what they want and not be taken
advantage of.

But where there are trusting people there will always be
enterprising no-goodnicks trying to ruin the fun for
everyone. Enter the Craigslist spammer. How does one spam
on Craigslist? There are two ways. The obvious, and
quickly detected method of dropping website links
directly in a posting, and the more underhanded,
legitimate looking post that waits for users to email them
so they can send them deceptive spam emails.

Make no mistake, this is spam. But unlike traditional
spam, we are essentially opting in by viewing and
replying to postings. Unfortunately, traditional spam
filters work by catching incoming emails. The popular
Bayesian spam filter keeps a database of words and their
"spaminess." So, how could we apply that to Craigslist,
to save us the trouble of unwittingly "opting in"?

Bayesian spam filters must be trained. We must start off
with decently sized corpuses of spam and ham text. Then
we are responsible for training the filter by telling it
if a body of text is good or bad. When dealing with 
email, the case is as simple as collecting the email, 
going through it one by one, and flagging the spam. With
Craigslist though, we are dealing with a website. We  will
have to go to Craigslist, rather than Craigslist coming
to us.

The plan is relatively simple: Scrape Craigslist at
arbitrary time intervals (every three minutes seems
reasonable), logging entries into a database. When an
entry becomes "flagged," that is logged too. The theory
being, if a posting is flagged, it is likely spam. There
is a small problem with this theory, and I will expand on
it later, but for now, let's assume any entry that is
flagged is indeed, spam.

##The Implementation

PHP works nicely for this project. We can use Curl to
scrape Craigslist and store the results in a
PostgreSQL database. We simply add it to our crontab and
let it run for a few months. (Yes, a few months). Then,
when we have enough data (5,500 records is a good sample
size, though Paul Graham suggests more like 8,000 - 4,000
spam, and 4,000 ham), we can finally write our Bayesian
filter.

Here is the crontab:

0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 *   *   *   *  php /path/to/clauto.php >/dev/null

For those unfamiliar with Bayesian classification, read
Paul Graham's famous essay in which he discusses the
virtues of statistical spam filtering. [1] Essentially,
the way this works is, by taking two corpuses of text
(one that is predetermined to be spam, and one that is
predetermined to be ham), we just need to store the
individual tokens into a hash map and keep track of how
many are spam vs. ham. Then, using Bayes' Rule, we can
calculate the probability that a posting is spam given
an "interesting" word in that text.

A simple implementation can be found at [3]. I have
translated it into PHP, which can be found find at [5].
So, each time we fire it up, it pulls out all the posts
in the database, stores them into a hash table as
individual tokens, and then that is our lookup table.
Then, it hits Craigslist, reads through each post, and
does the statistical comparison on them. If a post is
lower than 90% spam probability (we're being generous
here), it gets displayed along with its probability.

##Findings

The statistical filter looks to be working with great
accuracy, just as Graham had mentioned it would on email
spam. But some of my findings came before I even wrote
the filter, and was just examining the raw data.

Currently, my database has a total of 5,545 postings, of
which, 3,936 have been flagged (likely spam). That is,
almost 71% of all postings are not legit. Furthermore,
I kept track of which postings had pictures. Given that
most girls who post on Casual Encounters do so with 
privacy in mind, I reasoned that it would be rare to see a
legitimate post containing a picture. That was also proven
in the statistics. Of the 4,565 postings with pictures,
3,468 were flagged (almost 76%).

In the current implementation, this is not taken into
account, but if we could assign a weight to postings
with pictures, this could add to the accuracy.

##Caveats

The biggest concern I had when doing this was determining
how to define spam. The only way you could be 100%
certain if a post was spam would be to reply to it and
get an obvious spam email in return. I did attempt this
method in the beginning, but found it to be extremely
inefficient for two reasons: The mail host (Gmail in my
case) puts a cap on the number of emails sent out in a
given time period, so as to curb spam. We should all be
thankful for that, but the rapid fire-ness of my script
was getting me kicked off pretty fast. And two,
Craigslist ALSO curbs spam in this way. I should also
mention the third reason; this is slightly unethical,
actually making ME a spammer. So I scrapped this idea
early on, and decided that anything that gets flagged
shall be considered spam.

Unfortunately, this is far from accurate. Many legit
posts will get flagged for no reason whatsoever. Maybe
the girl doesn't reply to someone so he gets mad and
flags her. Maybe someone flags the wrong post. Maybe
someone is mischievous. Whatever the case, this is
unfortunate, but it is the best method we have right now.
Fortunately, it is not often that a spam post will go
unflagged, so we can be reasonably sure that our ham
corpus is clean. The only thing we need worry about are
false positives, and the filter is pretty inherently
forgiving, per Graham's suggestion.

##Hacking the Script

This script is mostly proof-of-concept and is not really
fit for mass consumption. One idea would be to provide
this as a service. A user comes to the site, enters their
city, and the current postings are displayed. Maybe even
pushed out as an RSS feed. I don't have the cash for a
decent host, and I'm really not sure this isn't violating
Craigslist's TOS, but I'm guessing it probably is.
Currently, Craigslist does not have an API, so we are
reduced to screen scraping, which is generally frowned
upon, legal or not.

Another idea I had was to write a Greasemonkey script or
Firefox addon that would do all the filtering as you went
to the site, but this could prove difficult for a couple
of reasons. The filtering relies on the subject and the
body of the post. On the main listings page, we are only
given the subject, so we would have to do an Ajax call to
get the body. The other - bigger - problem is memory. I
had to increase PHP's memory space to around 100 MB to
satisfy the requirements of the hash table. Keeping such
a hash table around in memory in Firefox does not sound
like something anyone would want.

Going back to the issue of not being 100% sure something
is spam; even though it's been flagged, I did consider
using fuzzy logic to assist in assigning values to the
tokens, assigning an arbitrary precision to spam vs. ham.
For instance, saying that we are only 75% sure that
everything in the spam corpus is actually spam, we could
scale the percentage that a word is spam. This was only
briefly considered, but I decided that I was happy with
the way things were without it.

##Conclusion - Not a Happy Ending

Sorry, gentlemen. It appears that Craigslist is in fact,
not the Holy Grail. Using Bayesian classification
however can greatly cut down on the wasted time of
writing to spammers. There ARE legitimate people on the
site. The trouble is wading through all the illegitimate 
posts and finding the real ones before someone else does.
So if you're going to use Casual Encounters, why not
increase your odds? Just once, I'd like to hear that
mathematics got someone laid.


##Sources

A Plan for Spam. Graham, Paul.
http://www.paulgraham.com/spam.html

Better Bayesian Filtering. Graham, Paul.
http://www.paulgraham.com/better.html

Bayesian Filtering.
http://www.shiffman.net/teaching/a2z/bayesian/

Bayesian spam filtering.
http://en.wikipedia.org/wiki/Bayesian_spam_filtering

Casual Encounters of the Third Kind. Detweiler, Brian.
https://github.com/bdetweiler/ce3k-bayesian-filter
