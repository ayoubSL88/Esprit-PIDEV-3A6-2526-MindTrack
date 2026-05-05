import pickle
import sys

with open('data/videos_data.pkl', 'rb') as f:
    data = pickle.load(f)
    
print(f"Nombre de vidéos: {len(data['df'])}")
print(f"Colonnes: {list(data['df'].columns)}")
print("\nExemple de vidéo:")
row = data['df'].iloc[0]
print(f"  - id: {row['id']}")
print(f"  - title: {row['title'][:50]}...")
print(f"  - url: {row['url']}")
