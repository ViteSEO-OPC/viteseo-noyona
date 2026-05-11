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

  var BLOCK_NAME = "noyona/cta-blogpost";
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
  var ToggleControl = wp.components.ToggleControl;
  var SelectControl = wp.components.SelectControl;
  var Fragment = wp.element.Fragment;
  var el = wp.element.createElement;

  function Edit(props) {
    var attributes = props.attributes || {};
    var setAttributes = props.setAttributes;
    var position = attributes.position || "left";
    var blockProps = useBlockProps({
      className: "cta-blogpost cta-blogpost--" + position + " cta-blogpost--editor",
    });

    return el(
      Fragment,
      null,
      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: __("CTA Settings", "noyona-childtheme"), initialOpen: true },
          el(TextControl, {
            label: __("Text", "noyona-childtheme"),
            value: attributes.text || "",
            onChange: function (value) {
              setAttributes({ text: value });
            },
          }),
          el(TextControl, {
            label: __("URL", "noyona-childtheme"),
            value: attributes.url || "",
            onChange: function (value) {
              setAttributes({ url: value });
            },
          }),
          el(SelectControl, {
            label: __("Position", "noyona-childtheme"),
            value: position,
            options: [
              { label: __("Left", "noyona-childtheme"), value: "left" },
              { label: __("Center", "noyona-childtheme"), value: "center" },
              { label: __("Right", "noyona-childtheme"), value: "right" },
            ],
            onChange: function (value) {
              setAttributes({ position: value });
            },
          }),
          el(ToggleControl, {
            label: __("Open link in new tab", "noyona-childtheme"),
            checked: !!attributes.newTab,
            onChange: function (value) {
              setAttributes({ newTab: !!value });
            },
          }),
          el(ToggleControl, {
            label: __("Show arrow", "noyona-childtheme"),
            checked: !!attributes.showArrow,
            onChange: function (value) {
              setAttributes({ showArrow: !!value });
            },
          })
        )
      ),
      el(
        "div",
        blockProps,
        el(
          "span",
          { className: "cta-blogpost__button" },
          el(
            "span",
            { className: "cta-blogpost__text" },
            attributes.text || __("Test", "noyona-childtheme")
          ),
          attributes.showArrow
            ? el("span", { className: "cta-blogpost__arrow", "aria-hidden": "true" }, "→")
            : null
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
      title: "CTA Blogpost",
      category: "widgets",
      icon: "megaphone",
      description: "Post CTA badge/button with position control.",
      keywords: ["cta", "blog", "button", "cta-blogpost", "call to action"],
      supports: {
        align: ["wide", "full"],
        html: false,
      },
      attributes: {
        text: {
          type: "string",
          default: "Test",
        },
        url: {
          type: "string",
          default: "#",
        },
        position: {
          type: "string",
          default: "left",
        },
        newTab: {
          type: "boolean",
          default: false,
        },
        showArrow: {
          type: "boolean",
          default: false,
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
