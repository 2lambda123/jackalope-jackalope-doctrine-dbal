<?php

class jackalope_Property extends jackalope_Item implements PHPCR_PropertyInterface {
    
    protected $value;
    
    public function __construct($data, $path, jackalope_Session $session, jackalope_ObjectManager $objectManager) {
        parent::__construct(null, $path, $session, $objectManager);
        $this->value = jackalope_Factory::get('Value', array($data['type'], $data['value']));
    }
    
    /**
     * Sets the value of this property to value. If this property's property
     * type is not constrained by the node type of its parent node, then the
     * property type may be changed. If the property type is constrained, then a
     * best-effort conversion is attempted.
     *
     * This method is a session-write and therefore requires a <code>save</code>
     * to dispatch the change.
     *
     * For Node objects as value:
     * Sets this REFERENCE OR WEAKREFERENCE property to refer to the specified
     * node. If this property is not of type REFERENCE or WEAKREFERENCE or the
     * specified node is not referenceable then a ValueFormatException is thrown.
     *
     * If value is an array:
     * If this property is not multi-valued then a ValueFormatException is
     * thrown immediately.
     *
     * Note: the Java API defines this method with multiple differing signatures.
     *
     * @param mixed $value The value to set
     * @return void
     * @throws PHPCR_ValueFormatException if the type or format of the specified value is incompatible with the type of this property.
     * @throws PHPCR_Version_VersionException if this property belongs to a node that is read-only due to a checked-in node and this implementation performs this validation immediately.
     * @throws PHPCR_Lock_LockException if a lock prevents the setting of the value and this implementation performs this validation immediately.
     * @throws PHPCR_ConstraintViolationException if the change would violate a node-type or other constraint and this implementation performs this validation immediately.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function setValue($value) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the value of this property as a Value object.
     *
     * The object returned is a copy of the stored value and is immutable.
     *
     * @return PHPCR_ValueInterface the value
     * @throws PHPCR_ValueFormatException if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getValue() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an array of all the values of this property. Used to access
     * multi-value properties. If the property is single-valued, this method
     * throws a ValueFormatException. The array returned is a copy of the
     * stored values, so changes to it are not reflected in internal storage.
     *
     * @return array of PHPCR_ValueInterface
     * @throws PHPCR_ValueFormatException if the property is single-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getValues() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a String representation of the value of this property. A
     * shortcut for Property.getValue().getString(). See Value.
     *
     * @return string A string representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a String is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getString() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a Binary representation of the value of this property. A
     * shortcut for Property.getValue().getBinary(). See Value.
     *
     * @return PHPCR_BinaryInterface A Binary representation of the value of this property.
     * @throws PHPCR_ValueFormatException if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getBinary() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an integer representation of the value of this property. A shortcut
     * for Property.getValue().getLong(). See Value.
     *
     * @return integer An integer representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a long is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getLong() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a double representation of the value of this property. A
     * shortcut for Property.getValue().getDouble(). See Value.
     *
     * @return float A float representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a double is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getDouble() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a BigDecimal representation of the value of this property. A
     * shortcut for Property.getValue().getDecimal(). See Value.
     *
     * @return float A float representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a BigDecimal is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getDecimal() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a DateTime representation of the value of this property. A
     * shortcut for Property.getValue().getDate(). See Value.
     *
     * @return DateTime A date representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a string is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getDate() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a boolean representation of the value of this property. A
     * shortcut for Property.getValue().getBoolean(). See Value.
     *
     * @return boolean A boolean representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a boolean is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getBoolean() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * If this property is of type REFERENCE, WEAKREFERENCE or PATH (or
     * convertible to one of these types) this method returns the Node to
     * which this property refers.
     * If this property is of type PATH and it contains a relative path, it is
     * interpreted relative to the parent node of this property. For example "."
     * refers to the parent node itself, ".." to the parent of the parent node
     * and "foo" to a sibling node of this property.
     *
     * @return PHPCR_NodeInterface the referenced Node
     * @throws PHPCR_ValueFormatException if this property cannot be converted to a referring type (REFERENCE, WEAKREFERENCE or PATH), if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
     * @throws PHPCR_ItemNotFoundException If this property is of type PATH or WEAKREFERENCE and no target node accessible by the current Session exists in this workspace. Note that this applies even if the property is a PATH and a property exists at the specified location. To dereference to a target property (as opposed to a target node), the method Property.getProperty is used.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getNode() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * If this property is of type PATH (or convertible to this type) this
     * method returns the Property to which this property refers.
     * If this property contains a relative path, it is interpreted relative
     * to the parent node of this property. Therefore, when resolving such a
     * relative path, the segment "." refers to the parent node itself, ".." to
     * the parent of the parent node and "foo" to a sibling property of this
     * property or this property itself.
     *
     * For example, if this property is located at /a/b/c and it has a value of
     * "../d" then this method will return the property at /a/d if such exists.
     *
     * @return PHPCR_PropertyInterface the referenced property
     * @throws PHPCR_ValueFormatException if this property cannot be converted to a PATH, if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
     * @throws PHPCR_ItemNotFoundException If no property accessible by the current Session exists in this workspace at the specified path. Note that this applies even if a node exists at the specified location. To dereference to a target node, the method Property.getNode is used.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getProperty() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the length of the value of this property.
     *
     * For a BINARY property, getLength returns the number of bytes.
     * For other property types, getLength returns the same value that would be
     * returned by calling strlen() on the value when it has been converted to a
     * STRING according to standard JCR propety type conversion.
     *
     * Returns -1 if the implementation cannot determine the length.
     *
     * @return integer an integer.
     * @throws PHPCR_ValueFormatException if this property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getLength() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an array holding the lengths of the values of this (multi-value)
     * property in bytes where each is individually calculated as described in
     * getLength().
     *
     * @return array an array of lengths (integers)
     * @throws PHPCR_ValueFormatException if this property is single-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getLengths() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the property definition that applies to this property. In some
     * cases there may appear to be more than one definition that could apply
     * to this node. However, it is assumed that upon creation or change of
     * this property, a single particular definition is chosen by the
     * implementation. It is that definition that this method returns. How this
     * governing definition is selected upon property creation or change from
     * among others which may have been applicable is an implementation issue
     * and is not covered by this specification.
     *
     * @return PHPCR_NodeType_PropertyDefinitionInterface a PropertyDefinition object.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getDefinition() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the type of this Property. One of:
     * * PropertyType.STRING
     * * PropertyType.BINARY
     * * PropertyType.DATE
     * * PropertyType.DOUBLE
     * * PropertyType.LONG
     * * PropertyType.BOOLEAN
     * * PropertyType.NAME
     * * PropertyType.PATH
     * * PropertyType.REFERENCE
     * * PropertyType.WEAKREFERENCE
     * * PropertyType.URI
     *
     * The type returned is that which was set at property creation. Note that
     * for some property p, the type returned by p.getType() will differ from
     * the type returned by p.getDefinition.getRequiredType() only in the case
     * where the latter returns UNDEFINED. The type of a property instance is
     * never UNDEFINED (it must always have some actual type).
     *
     * @return integer an int
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getType() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns TRUE if this property is multi-valued and FALSE if this property
     * is single-valued.
     *
     * @return boolean TRUE if this property is multi-valued; FALSE otherwise.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function isMultiple() {
        throw new jackalope_NotImplementedException();
    }

}

