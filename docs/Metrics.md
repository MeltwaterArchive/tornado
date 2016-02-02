# Metrics

Namespace: `tornado.[production|development|test].[tornado|api|console]`

## Some metrics:

Increment:

```
tornado.production.tornado.request
tornado.production.tornado.request.method.get
tornado.production.tornado.request.method.post
tornado.production.tornado.request.method.put
tornado.production.tornado.request.method.delete
tornado.production.tornado.exception
tornado.production.tornado.response
tornado.production.tornado.response.code.200
tornado.production.tornado.response.code.201
tornado.production.tornado.response.code.400
tornado.production.tornado.response.code.401
tornado.production.tornado.response.code.403
tornado.production.tornado.response.code.404
tornado.production.tornado.response.code.500
tornado.production.tornado.render_time.count
tornado.production.tornado.db_query
tornado.production.tornado.analyze.count        # analyze queries to pylon
```

Timer:

```
tornado.production.tornado.render_time
tornado.production.tornado.db_query.total_time
tornado.production.tornado.analyze              # execution time of analyze queries to pylon
```
