#!/bin/bash

# Define base directories
BASE_DIR="src/app/code/Formula"
INGREDIENT_DIR="${BASE_DIR}/Ingredient"
SKINTYPE_DIR="${BASE_DIR}/SkinType"

# Remove existing SkinType directory if it exists
if [ -d "$SKINTYPE_DIR" ]; then
    rm -rf "$SKINTYPE_DIR"
    echo "Removed existing SkinType directory"
fi

# Create SkinType module base directory
mkdir -p "$SKINTYPE_DIR"
echo "Created new SkinType directory"

# Function to copy and modify a file
copy_and_modify_file() {
    local src_file=$1
    local relative_path=$2
    
    # Create the new relative path by replacing Ingredient with SkinType
    local new_relative_path=$(echo "$relative_path" | sed -e 's/Ingredient/SkinType/g' -e 's/ingredient/skintype/g')
    
    # Create the destination file path
    local dest_file="${SKINTYPE_DIR}/${new_relative_path}"
    
    # Create destination directory if it doesn't exist
    mkdir -p "$(dirname "$dest_file")"
    
    # Copy and replace content in one step
    sed -e 's/Ingredient/SkinType/g' \
        -e 's/ingredient/skintype/g' \
        -e 's/INGREDIENT/SKINTYPE/g' \
        -e 's/formula_ingredient/formula_skintype/g' \
        "$src_file" > "$dest_file"
    
    echo "Created: $dest_file with modified content"
}

# Process all files in the Ingredient directory
find "$INGREDIENT_DIR" -type f | while read src_file; do
    # Get the relative path from the Ingredient directory
    relative_path="${src_file#$INGREDIENT_DIR/}"
    
    # Copy and modify the file
    copy_and_modify_file "$src_file" "$relative_path"
done

echo "SkinType module has been created successfully!"
echo "All files are placed in the correct directory structure."
echo ""
echo "Next steps:"
echo "1. Review the generated files"
echo "2. Update the db_schema.xml for SkinType-specific schema"
echo "3. Update the form and listing UI components"
echo "4. Configure the WebAPIs"