# Overview

[FingerPrinted Contigs](FPC) is a map format that one is not likely to come across these days.  This is because genome sequencing and mapping with [BAC-contig](https://en.wikipedia.org/wiki/Contig#BAC_contigs) technology has been replaced with technologies like genotyping by sequencing and next-generation sequencing.  In short, a genome would bebroken up into pieces and cloned into bacterial artificial chromosomes ([BACs](https://en.wikipedia.org/wiki/Bacterial_artificial_chromosome)). FPC would attempt to piece those fragmented BACs back together.

Nevertheless, one might be required to interpret FPC format files.  Towards this end, we wrote this tool to convert FPC maps to [CMap format](http://gmod.org/wiki/CMap).

# Usage
```bash
php convert.php input.fpc > output.cmap
```
