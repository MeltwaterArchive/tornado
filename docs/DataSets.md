# DataSets

## Data structure

The data contained in `DataSet` objects is structured as a tree, with top nodes
representing measures and child nodes representing dimensions and their values,
each leaf and node containing a value and whether the value is redacted or not:

```
{
    "measure:interactions": {
        "%VALUE%": 500,
        "%REDACTED%": false,
        "dimension:region":{
            "alabama": {
                "%VALUE%": 200,
                "%REDACTED%": false,
                "dimension:age": {
                    "18-24": {
                        "%VALUE%": 200,
                        "%REDACTED%": false,
                        "dimension:gender": {
                            "male": {
                                "%VALUE%": 200,
                                "%REDACTED%": false
                            }
                        }
                    }
                }
            },
            "alaska": {
                "%VALUE%": 300,
                "%REDACTED%": false,
                "dimension:age": {
                    "18-24": {
                        "%VALUE%": 300,
                        "%REDACTED%": false,
                        "dimension:gender": {
                            "male": {
                                "%VALUE%": 300,
                                "%REDACTED%": false
                            }
                        }
                    }
                }
            }
        }
    }
}
```

## Pivoting

As `DataSet` objects represent the result of a tree-like search, the resulting
data contained in them (and accessed via `getData`) can also be viewed as a tree
where the leaves are at a constant depth. With this in mind, you can view each
node as part of a coordinate in n-dimensional space, where the depth of the tree
corresponds with a dimension. For example, the following structure represents
(`age`, `gender`, `region`):

```json
{
   "18-24": {
        "male": {
            "alabama": 200,
            "alaska": 300
        }
    }
}
```

Therefore, the item at (`18-24`,`male`,`alabama`) is `200`. Given that the depth
is constant, we can transform - or pivot - the data so that we reorder how we
view the data, yet still retain it in its appropriate position; the following
structure is identical to the above in terms of content, but represents
(`region`, `gender`, `age`):

```json
{
    "alabama": {
        "male": {
            "18-24": 200
        }
    },
    "alaska": {
        "male": {
            "18-24": 300
        }
    }
}
```

In this way, we can pivot a tree representing the same set of Dimensions but
in a different order without losing the integrity of the data it represents, but
representing it in a different fashion depending on the charts to be drawn.

### Pivoting DataSets

In the case of the `DataSet` structure, there are a few more elements in the
tree to represent the different dimensions, but the principle is the same; we
can convert;

```json
{
    "dimension:gender": {
        "male": {
            "dimension:age": {
                "18-24": {
                    "dimension:region": {
                        "alabama": 200,
                        "alaska": 300
                    }
                }
            }
        }
    }
}
```

into a tree where each node represents a tuple:

```json
{
    "dimension:gender:male": {
        "dimension:age:18-24": {
            "dimension:region:alabama": 200,
            "dimension:region:alaska": 300
        }
    }
}
```

We can then flatten this into a single-dimensional array for ease of traversal:

```json
{
    "dimension:gender:male||dimension:age:18-24||dimension:region:alabama": 200,
    "dimension:gender:male||dimension:age:18-24||dimension:region:alaska": 300
}
```

Before splitting the path into its constituent parts, and reordering by the
desired new dimensions:

```json
{
    "dimension:region:alabama||dimension:age:18-24||dimension:gender:male": 200,
    "dimension:region:alaska||dimension:age:18-24||dimension:gender:male": 300
}
```

This can then be "unflattened" back into the tree structure:

```json
{
    "dimension:region":{
        "alabama": {
            "dimension:age": {
                "18-24": {
                    "dimension:gender": {
                        "male": 200
                    }
                }
            }
        },
        "alaska": {
            "dimension:age": {
                "18-24": {
                    "dimension:gender": {
                        "male": 300
                    }
                }
            }
        }
    }
}
```

It's worth noting that the intermediate results returned by the `/pylon/analyze`
API - the values against nodes of the tree - must necessarily be abandoned
during this process; there is not enough information to recompose the original
information. Indeed, some of the dimensions represented are not
mutually-exclusive, and so it is not valid to combine them.

## Baselines

A baseline comparison is constructed by comparing the actual distribution of a
subset of a general population against the assumption it is equally distributed
across its various demographic or other aspects.

For example, we might wish to compare the following data representing those
talking about the brand Canon:

```json
{
   "18-24": 100,
   "25-34": 300,
   "35-44": 600,
   "45-54": 700,
   "65+": 300
}
```

against the following data representing those talking about cameras in general:

```json
{
   "18-24": 400,
   "25-34": 800,
   "35-44": 900,
   "45-54": 800,
   "65+": 400
}
```

To do this, we normalize the second data set, by determining the ratio of the
total population of our target data versus our baseline data:

```php
$originPopulation = 100 + 300 + 600 + 700 + 300; // 2000
$baselinePopulation = 400 + 900 + 900 + 800 + 500; // 3500

$ratio = $originPopulation / $baselinePopulation; // 0.571
```

We then multiply each item in our baseline data to get the *expected* numbers if
we assume the proportion of those talking about Canon is constant across all
ages:

```json
{
   "18-24": 228.57,
   "25-34": 457.14,
   "35-44": 514.29,
   "45-54": 457.14,
   "65+": 228.57
}
```

Finally, we assemble the comparison; as a result, the analyst can determine that
Canon is *underindexed* in younger demographics, and *overindexed* in the older
age ranges.

```json
{
   "18-24": [100, 229],
   "25-34": [300, 457],
   "35-44": [600, 514],
   "45-54": [700, 457],
   "65+": [300, 229]
}
```
