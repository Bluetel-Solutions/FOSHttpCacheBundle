Tagging
=======

**Works with**:

* :ref:`Varnish <foshttpcache:varnish_tagging>`

If your application has many intricate relationships between cached items,
which makes it complex to invalidate them by route, cache tagging will be
useful. It helps you with invalidating many-to-many relationships between
content items.

Cache tagging, or more precisely `Tagged Cache Invalidation`_, a simpler
version of `Linked Cache Invalidation`_ (LCI), allows you to:

* assign tags to your applications’s responses (e.g., ``articles``, ``article-42``)
* :ref:`invalidate the responses by tag <foshttpcache:tags>` (e.g., invalidate
  all responses that are tagged ``article-42``)

Basic Configuration
-------------------

First :ref:`configure your proxy <foshttpcache:varnish_tagging>` for tagging.
Then enable tagging in your application configuration:

.. code-block:: yaml

    fos_http_cache:
        tags:
            enabled: true

For more information, see :doc:`/reference/configuration/tags`.

Setting and Invalidating Tags
-----------------------------

You can tag responses in different ways: with the tag handler from PHP or with
a twig function, from configuration or using annotations on controller actions.

Tagging from Code
~~~~~~~~~~~~~~~~~

Inject the ``TagHandler`` (service ``fos_http_cache.handler.tag_handler``) and
use ``addTags($tags)`` to add tags that will be set on the response::

    use FOS\HttpCacheBundle\Handler\TagHandler;

    class NewsController
    {
        /**
         * @var TagHandler
         */
        private $tagHandler;

        public function articleAction($id)
        {
            $this->tagHandler->addTags(array('news', 'news-' . $id));

            // ...
        }
    }

To invalidate tags, call ``TagHandler::invalidateTags($tags)``::

    class NewsController
    {
        // ...

        public function editAction($id)
        {
            // ...

            $this->tagHandler->invalidateTags(array('news-' . $id));

            // ...
        }
    }

See the :ref:`Tag Handler reference <tag_handler_addtags>` for full details.

Tagging from Twig Templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~

In situations where a page is assembled in the templating layer, it can be more
convenient to add tags from inside the template. This works the same way as
with the tag handler and can also be mixed with the other methods:

.. code-block:: jinja

    {# template.html.twig #}
    {{ fos_httpcache_tag('mytag') }}
    {{ fos_httpcache_tag(['tag-one', 'tag-two']) }}

.. hint::

    This twig function never outputs anything into the template but is only
    called for the side effect of adding the tag to the response header.

.. note::

    Tag invalidation from twig would be a strange architecture and is therefore
    not supported.

Tagging with Configuration Rules
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Alternatively, you can :doc:`configure rules </reference/configuration/tags>`
for setting and invalidating tags:

.. code-block:: yaml

    // app/config/config.yml
    fos_http_cache:
        tags:
            rules:
                -
                    match:
                        path: ^/news/article
                    tags: [news]

Now if a :term:`safe` request matches the criteria under ``match``, the response
will be tagged with ``news``. When an unsafe request matches, the tag ``news``
will be invalidated.

Tagging with Controller Annotations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Add the ``@Tag`` annotations to your controllers to set and invalidate tags::

    use FOS\HttpCacheBundle\Configuration\Tag;

    class NewsController
    {
        /**
         * @Tag("news", expression="'news-'~id")
         */
        public function articleAction($id)
        {
            // Assume $id equals 123
        }
    }

If ``articleAction`` handles a :term:`safe` request, a tag ``news-123`` is set
on the response. If a client tries to update or delete news article 123 with an
unsafe request to ``articleAction``, such as POST or DELETE, tag ``news-123``
is invalidated.

See the :ref:`@Tag reference <tag>` for full details.

.. _Tagged Cache Invalidation: http://blog.kevburnsjr.com/tagged-cache-invalidation
.. _Linked Cache Invalidation: http://tools.ietf.org/html/draft-nottingham-linked-cache-inv-03
.. _expressions: http://symfony.com/doc/current/components/expression_language/index.html
