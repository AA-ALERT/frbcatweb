#!/usr/bin/python
# import warnings
# from astropy.utils.exceptions import AstropyWarning
# warnings.simplefilter('ignore', category=AstropyWarning)

import sys, os, warnings

if not os.path.exists(sys.argv[1]):
    sys.stderr.write('ERROR: CSV file ' + sys.argv[1] + ' not found\n')
    sys.exit(1)

csv_file = sys.argv[1]
xml_file = sys.argv[2]

from astropy.utils.exceptions import AstropyDeprecationWarning
with warnings.catch_warnings():
  warnings.simplefilter('ignore', AstropyDeprecationWarning)

from astropy.io import ascii,votable

votable.from_table(ascii.read(csv_file, header_start=0,data_start=1)).to_xml(xml_file)
