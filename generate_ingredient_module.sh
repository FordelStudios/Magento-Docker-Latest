#!/bin/bash

# Define base directories
BASE_DIR="src/app/code/Formula"
BRAND_DIR="${BASE_DIR}/Brand"
INGREDIENT_DIR="${BASE_DIR}/Ingredient"

# Remove existing Ingredient directory if it exists
if [ -d "$INGREDIENT_DIR" ]; then
    rm -rf "$INGREDIENT_DIR"
    echo "Removed existing Ingredient directory"
fi

# Create Ingredient module base directory
mkdir -p "$INGREDIENT_DIR"
echo "Created new Ingredient directory"

# Function to copy and modify a file
copy_and_modify_file() {
    local src_file=$1
    local relative_path=$2
    
    # Create the new relative path by replacing Brand with Ingredient
    local new_relative_path=$(echo "$relative_path" | sed -e 's/Brand/Ingredient/g' -e 's/brand/ingredient/g')
    
    # Create the destination file path
    local dest_file="${INGREDIENT_DIR}/${new_relative_path}"
    
    # Create destination directory if it doesn't exist
    mkdir -p "$(dirname "$dest_file")"
    
    # Copy and replace content in one step
    sed -e 's/Brand/Ingredient/g' \
        -e 's/brand/ingredient/g' \
        -e 's/BRAND/INGREDIENT/g' \
        -e 's/formula_brand/formula_ingredient/g' \
        "$src_file" > "$dest_file"
    
    echo "Created: $dest_file with modified content"
}

# Process all files in the Brand directory
find "$BRAND_DIR" -type f | while read src_file; do
    # Get the relative path from the Brand directory
    relative_path="${src_file#$BRAND_DIR/}"
    
    # Copy and modify the file
    copy_and_modify_file "$src_file" "$relative_path"
done

echo "Ingredient module has been created successfully!"
echo "All files are placed in the correct directory structure."
echo ""
echo "Next steps:"
echo "1. Review the generated files"
echo "2. Update the db_schema.xml for Ingredient-specific schema"
echo "3. Update the form and listing UI components"
echo "4. Configure the WebAPIs"