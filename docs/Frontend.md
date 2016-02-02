# TORNADO Front-end
A collection of information regarding our methodologies and stack. Also a WIP.

## Getting started
1. Pull the latest codebase
2. Fire up the console, go to `/src` and do `npm install` to install all the necessary node modules
3. Do `bower install` to fetch `bourbon`, `neat` and anything else defined in our bower.json file
4. Do `grunt` to watch and compile your SCSS files, minify any images etc
5. Victory!

## Information

#### TWIG

Server side templates are built by using the [Twig](twig.sensiolabs.org) template engine.

There are some global twig variables accessible in each template:
- `sessionUser` array representation of an authenticad user public data

#### HTML
We're using a loose version of BEM for the markup. It's a little more verbose, but arguably more semantic and readable. For more information you can take a look [here](http://csswizardry.com/2013/01/mindbemding-getting-your-head-round-bem-syntax/) or directly at the codebase.

#### CSS (SCSS)
Using:
* `Bourbon` for Sass - A simple and lightweight mixin library for SASS
* `Neat` for Bourbon - A lightweight semantic grid framework for SASS and Bourbon
* `.scss-lint.yml` with rules tweaked for the BEM methodology

#### JS
As per our `.jscsrc` config, we are following the _airbnb_ guidelines, but with indentation of _4_ spaces.
