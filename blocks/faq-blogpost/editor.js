(function (wp) {
  if (
    !wp ||
    !wp.blocks ||
    !wp.blockEditor ||
    !wp.components ||
    !wp.element
  ) {
    return;
  }

  var BLOCK_NAME = "noyona/faq-blogpost";
  var __ =
    wp.i18n && wp.i18n.__
      ? wp.i18n.__
      : function (text) {
          return text;
        };

  var registerBlockType = wp.blocks.registerBlockType;
  var unregisterBlockType = wp.blocks.unregisterBlockType;
  var getBlockType = wp.blocks.getBlockType;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var TextControl = wp.components.TextControl;
  var TextareaControl = wp.components.TextareaControl;
  var ToggleControl = wp.components.ToggleControl;
  var Button = wp.components.Button;
  var Fragment = wp.element.Fragment;
  var useState = wp.element.useState;
  var useEffect = wp.element.useEffect;
  var el = wp.element.createElement;

  var DEFAULT_ITEMS = [
    {
      question: "Duis aute irure dolor in reprehenderit?",
      answer:
        "Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
    },
    {
      question: "Excepteur sint occaecat cupidatat non proident?",
      answer:
        "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.",
    },
    {
      question: "Ut enim ad minim veniam, quis nostrud?",
      answer:
        "Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.",
    },
  ];

  function normalizeItems(items) {
    var source = Array.isArray(items) && items.length ? items : DEFAULT_ITEMS;
    return source.map(function (item) {
      return {
        question:
          item && typeof item.question === "string"
            ? item.question
            : "FAQ question",
        answer:
          item && typeof item.answer === "string"
            ? item.answer
            : "FAQ answer",
      };
    });
  }

  function Edit(props) {
    var attributes = props.attributes || {};
    var setAttributes = props.setAttributes;
    var items = normalizeItems(attributes.items);
    var openFirst = !!attributes.openFirst;
    var initialOpen = openFirst ? 0 : -1;
    var openState = useState(initialOpen);
    var openIndex = openState[0];
    var setOpenIndex = openState[1];

    useEffect(
      function () {
        if (!openFirst) {
          setOpenIndex(-1);
          return;
        }
        if (openIndex < 0 && items.length) {
          setOpenIndex(0);
        }
      },
      [openFirst, openIndex, items.length]
    );

    function updateItem(index, key, value) {
      var nextItems = items.map(function (item, itemIndex) {
        if (itemIndex !== index) {
          return item;
        }
        return Object.assign({}, item, { [key]: value });
      });
      setAttributes({ items: nextItems });
    }

    function addItem() {
      setAttributes({
        items: items.concat([{ question: "", answer: "" }]),
      });
    }

    function removeItem(index) {
      if (items.length <= 1) {
        return;
      }
      var nextItems = items.filter(function (_, itemIndex) {
        return itemIndex !== index;
      });
      setAttributes({ items: nextItems });
      if (openIndex >= nextItems.length) {
        setOpenIndex(nextItems.length - 1);
      }
    }

    var blockProps = useBlockProps({
      className: "faq-blogpost faq-blogpost--editor",
    });

    return el(
      Fragment,
      null,
      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: __("Content", "noyona-childtheme"), initialOpen: true },
          el(TextControl, {
            label: __("Heading", "noyona-childtheme"),
            value: attributes.heading || "",
            onChange: function (value) {
              setAttributes({ heading: value });
            },
          }),
          el(TextControl, {
            label: __("Subheading", "noyona-childtheme"),
            value: attributes.subheading || "",
            onChange: function (value) {
              setAttributes({ subheading: value });
            },
          }),
          el(ToggleControl, {
            label: __("Open first item by default", "noyona-childtheme"),
            checked: openFirst,
            onChange: function (value) {
              setAttributes({ openFirst: !!value });
            },
          })
        ),
        el(
          PanelBody,
          { title: __("FAQ Items", "noyona-childtheme"), initialOpen: false },
          items.map(function (item, index) {
            return el(
              "div",
              { className: "faq-blogpost-editor__item-fields", key: "faq-item-" + index },
              el(
                "p",
                { className: "faq-blogpost-editor__item-heading" },
                __("Item", "noyona-childtheme") + " " + (index + 1)
              ),
              el(TextControl, {
                label: __("Question", "noyona-childtheme"),
                value: item.question,
                onChange: function (value) {
                  updateItem(index, "question", value);
                },
              }),
              el(TextareaControl, {
                label: __("Answer", "noyona-childtheme"),
                value: item.answer,
                rows: 4,
                onChange: function (value) {
                  updateItem(index, "answer", value);
                },
              }),
              el(
                Button,
                {
                  variant: "secondary",
                  isDestructive: true,
                  disabled: items.length <= 1,
                  onClick: function () {
                    removeItem(index);
                  },
                },
                __("Remove Item", "noyona-childtheme")
              )
            );
          }),
          el(
            Button,
            {
              variant: "primary",
              onClick: addItem,
            },
            __("Add FAQ Item", "noyona-childtheme")
          )
        )
      ),
      el(
        "section",
        blockProps,
        el(
          "div",
          { className: "faq-blogpost__inner" },
          el(
            "h2",
            { className: "faq-blogpost__heading" },
            attributes.heading || __("Frequently Asked Questions", "noyona-childtheme")
          ),
          el(
            "p",
            { className: "faq-blogpost__subheading" },
            attributes.subheading ||
              __("Lorem ipsum dolor sit amet, consectetur adipiscing elit", "noyona-childtheme")
          ),
          el(
            "div",
            { className: "faq-blogpost__items" },
            items.map(function (item, index) {
              var isOpen = openIndex === index;
              return el(
                "details",
                {
                  className: "faq-blogpost__item",
                  open: isOpen,
                  key: "faq-preview-item-" + index,
                },
                el(
                  "summary",
                  {
                    className: "faq-blogpost__summary",
                    onClick: function (event) {
                      event.preventDefault();
                      setOpenIndex(isOpen ? -1 : index);
                    },
                  },
                  el(
                    "span",
                    { className: "faq-blogpost__question" },
                    item.question || __("FAQ question", "noyona-childtheme")
                  ),
                  el("span", { className: "faq-blogpost__toggle", "aria-hidden": "true" })
                ),
                el(
                  "div",
                  { className: "faq-blogpost__answer" },
                  el(
                    "p",
                    null,
                    item.answer ||
                      __("FAQ answer", "noyona-childtheme")
                  )
                )
              );
            })
          )
        )
      )
    );
  }

  function registerBlock() {
    var existing = getBlockType(BLOCK_NAME);
    if (existing) {
      unregisterBlockType(BLOCK_NAME);
    }

    registerBlockType(BLOCK_NAME, {
      apiVersion: 3,
      title: "FAQ Blogpost",
      category: "widgets",
      icon: "editor-help",
      description: "FAQ accordion section for blog posts.",
      supports: {
        align: ["full", "wide"],
        html: false,
      },
      attributes: {
        heading: {
          type: "string",
          default: "Frequently Asked Questions",
        },
        subheading: {
          type: "string",
          default: "Lorem ipsum dolor sit amet, consectetur adipiscing elit",
        },
        items: {
          type: "array",
          default: DEFAULT_ITEMS,
        },
        openFirst: {
          type: "boolean",
          default: true,
        },
      },
      edit: Edit,
      save: function () {
        return null;
      },
    });
  }

  if (wp.domReady) {
    wp.domReady(registerBlock);
    return;
  }

  registerBlock();
})(window.wp);
