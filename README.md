# Plazi overlay

Overlaying boxes from Plazi treatment XML onto PDF images to understand Plazi coordinate system and relationship to source PDFs.

Plazi page coordinates are consistent with converting PDF to 192 DPI images, e.g.

```
pdftoppm -png -r 190 document.pdf images/page
```

The bounding boxes are [minx, miny, maxx, maxy] (although sometimes we see boxes that break this pattern).

## Example

Ischnura praematura sp. nov. (Odonata: Zygoptera: Coenagrionidae): a species from Yunnan (China) whose females mate in the teneral state https://doi.org/10.11646/zootaxa.5087.1.3

Treament https://tb.plazi.org/GgServer/html/03F587E2B22862643686FDEF53E2F87E
