# BOLD Versions

## Introduction

Exploring how barcode identifications have changed between data releases. Past versions of BOLD data packages downloaded from BOLD. For each TSV file we extract individual records and store just the fields we are interested in. In this case,`bin_uri`, `identification`, identification_method`, and `identified_by`. Note that field names can change between data packages, so we may have to translate field names, or assemble a field’s value from other fields (e.g., taxonomic classification).

Rather than store all the data we use [Tuple-versioning](https://en.wikipedia.org/wiki/Tuple-versioning) so that we store values for `processid` and the various data fields, together values for `valid_from` and `valid_to`. The first time a combination of values is found we set `valid_from` to the YYYY-MM-DD date of the corresponding data package, and `valid_to` to `NULL`. Note that we may have multiple barcodes for a given `processid` (e.g., for different genes) so we index on both `processid` and `marker_code`. We also compute a MD5 hash of the data for a barcode to enable fast lookup of a particular set of values. Also note that a hash is not sufficient to identify an edit as the same set of values may have more than one period of validity. For example, a barcode may be in one BIN, then move to another, then move back again.

When we load the first data package all rows in the database will have NULL values for `valid_to`. We then add the remaining data packages from oldest to most recent. For each barcode if the data in the current package is the same as that already in the database we do nothing. If the data has changed we (a) set `valid_to` for the existing row to the YYYY-MM-DD data of the current data package, and add a new row with `valid_from` set to the same date. 

At the end of this process we have a list of values for the selected fields for each barcode, together with the time span that those values were valid.

## Examples

Papers to look at

- https://peerj.com/articles/11192/#results


### BOLD:AAH8697

BOLD:AAH8697 is listed in the description of https://tb.plazi.org/GgServer/html/23F3367C3AD082ADE37550FFA9C68FDD/1 Heterogamus donstonei but this BIN is currently not associated with name in BOLD. Which BIN has the type specimen?

Note that we can search on consensus sequences in BOLD View.

```
AATTTTATATTTTATYTTTGGGATATGGGCTGGTATAATTGGTATRTCTATAAGATTAATTATTCGAATAGAGTTGGGKGTGTGTGGAAGRYTATTAATAAATGATCAAATTTATAATGGTATAGTRACTTTACAYGCTTTTATTATRATTTTTTTTATAGTTATACCTATTATRATTGGAGGRTTTGGTAATTGAYTAATTCCTTTAATATTAGGRTCTCCTGATATAGCTTTYCCWCGAATAAATAATATAAGTTTTTGATTATTAATTCCTTCTTTAATTTTATTAATTRYTAGGGGGGTGATAAATGTRGGTGTTGGYACGGTTGAACAGTTTAYCCYCCYTTATCTTCRTTAGTAGGGCATATAGGGRTRTCTGTAGATTTATCAATTTTTTCYTTACAYTTAGCTGGGGCTTCTTCAATTATAGGGGCTATTAATTTTATTACAACTATTTTTAATATGAATTTATTTAAAATTAAAATAGATCAGATGTCTTTATTTATTTGATCTGTTTTAATTACAGCATTTTTATTATTRTTATCTTTRCCTGTATTAGCAGGGGCTATTACTATATTATTAACGGATCGAAATTTAAAYACTACATTTTTTGATTTTTCAGGGGGGARGGGGATCCTATTTTTATTYCAACATTTATTTTGATTTTTTG.
```

## Queries



## Reading

- Meier, R., Blaimer, B.B., Buenaventura, E., Hartop, E., von Rintelen, T., Srivathsan, A. and Yeo, D. (2022), A re-analysis of the data in Sharkey et al.’s (2021) minimalist revision reveals that BINs do not deserve names, but BOLD Systems needs a stronger commitment to open science. Cladistics, 38: 264-275. https://doi.org/10.1111/cla.12489









