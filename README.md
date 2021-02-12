YAML Formatter
==============

Pretty-up your YAML files!

Features:

- Automatically add anchors/aliases for repeated data
- Remove unneeded whitespace
- Correct indentation

Usage
-----

Install with Composer:

    composer require dragoonboots/yamlformatter

See available options with `vendor/bin/yamlformatter format --help`

    Usage:
      format [options] [--] <INPUT> [<OUTPUT>]
    
    Arguments:
      INPUT                                  Input file or directory
      OUTPUT                                 Output file or directory. Will overwrite when not specified.
    
    Options:
      -o, --override                         Override .yamlformatter.json options
          --indent=INDENT                    Number of spaces to indent [default: 2]
          --no-multiline-literal             Write string literals with multiple lines with embedded escaped newlines instead of as a multi-line literal
          --no-null-tilde                    Write null values as "null"
          --no-anchors                       Do not create reference anchors
          --anchors-include=ANCHORS-INCLUDE  Regular expression for YAML path to generate anchors for. Keys are separated by periods. Defaults to generating anchors for everything. (multiple values allowed)
          --anchors-exclude=ANCHORS-EXCLUDE  Regular expression for YAML path to not generate anchors for. (multiple values allowed)
      -h, --help                             Display help for the given command. When no command is given display help for the list command
      -q, --quiet                            Do not output any message
      -V, --version                          Display this application version
          --ansi                             Force ANSI output
          --no-ansi                          Disable ANSI output
      -n, --no-interaction                   Do not ask any interactive question
      -v|vv|vvv, --verbose                   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

For example:

Given a directory called `resources/data`, this will add refs to all files in place:

    vendor/bin/yamlformatter format resources/data

Do not generate refs automatically:

    vendor/bin/yamlformatter format --no-anchors resources/data

Use a different output directory `resources/cleaned`:

    vendor/bin/yamlformatter format resources/data resources/cleaned

Options
-------
If a file called `.yamlformatter.json` is in the input directory, it will be used to set options.  *Any options set in
that file will override those passed on the command line, unless `--override` is used!*

The file contains a JSON object with the following properties. These properties serve the same function as the
command-line switches, although some of them are negated:

- indentation
- multiLineLiteral
- nullAsTilde
- anchors
    - include
    - exclude

Here is an example:

    {
      "indentation": 2,
      "multiLineLiteral": true,
      "nullAsTilde": true,
      "anchors": {
        "exclude": [
          "`.+\\.short_effect`"
        ]
      }
    }

If you use an editor like PHPStorm that supports JSON Schema, a validation schema is
available [here](https://github.com/danielskeenan/yamlformatter/blob/master/resources/yamlformatter.json).

License
-------

Licensed under the [MIT license](https://github.com/danielskeenan/yamlformatter/blob/master/LICENSE.md).
