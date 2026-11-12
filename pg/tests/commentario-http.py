"""Read-only HTTP acceptance checks for fixtures from commentario.php."""
import json
import re
import sys
import urllib.request
import xml.etree.ElementTree as ET

base = sys.argv[1].rstrip('/')
with open(sys.argv[2]) as f:
    ids = json.load(f)
checks = 0

def check(condition, message):
    global checks
    assert condition, message
    checks += 1

def get(path):
    with urllib.request.urlopen(base + path) as response:
        check(response.status == 200, path + ' resolves')
        return response.read().decode('utf-8')

def feed_links(path):
    return [item.findtext('link') for item in ET.fromstring(get(path)).findall('./channel/item')]

archive = get('/commentario/')
check(f'post-{ids["book"]}' in archive and f'post-{ids["first"]}' not in archive, 'Archive lists books only')
book = get('/commentario/atti/')
check('1:1-14' in book and 'Excursus' in book, 'Book has automatic passage index')
intro = get('/commentario/atti/introduzione/')
first = get('/commentario/atti/1-1-14/')
check('Puntata precedente' in first and 'Puntata successiva' in first, 'Previous/next sibling labels')
check('/commentario/atti/1-15-26/' in first and '/commentario/atti/excursus-il-regno/' not in first, 'Navigation skips excursus')
check(not re.search(r'<time\b|class="post-navigation', first), 'No dates or chronological post navigation')
check('Puntata riservata' not in book, 'Draft absent from public index')
home = get('/')
check(f'post-{ids["blog"]}' in home and f'post-{ids["first"]}' not in home, 'Blog homepage excludes commentary')
blog_archive = get(ids['blog_archive'])
check(f'post-{ids["first"]}' not in blog_archive, 'Date archive excludes commentary')
main_feed = feed_links('/feed/')
check(not any('/commentario/' in url for url in main_feed), 'Main feed excludes commentary')
commentary_feed = feed_links('/commentario/feed/')
check(base + '/commentario/atti/1-1-14/' in commentary_feed, 'Commentary feed includes published entries, not just books')
check(base + '/commentario/atti/2-1-4/' not in commentary_feed, 'Commentary feed excludes drafts')
search = get('/?s=PGQASEARCH')
check(f'post-{ids["first"]}' in search and f'post-{ids["draft"]}' not in search, 'Site search includes published entries and hides drafts')
check(not re.search(r'PHP (?:Warning|Notice|Fatal error)', first + book + archive + search), 'No rendered PHP errors')
print(f'{checks} HTTP assertions passed.')
