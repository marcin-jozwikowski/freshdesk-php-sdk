all: test

install:
	composer install

test: install
	vendor/bin/phpunit

# docs: install
# 	vendor/bin/phpdoc  -d src -t docs/ --template="xml" --title="Freshdesk PHP SDK (API v2) Documentation"
# 	vendor/bin/phpdocmd docs/structure.xml docs
# 	rm -rf ./docs/phpdoc-cache-*
# 	git add .
# 	git commit -m "Updated documentation [ci skip]"