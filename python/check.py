import pickle

with open('data/videos_data.pkl', 'rb') as f:
    data = pickle.load(f)
    print(f"Nombre de vidéos: {len(data['df'])}")
    print("Premières vidéos:")
    for i, row in data['df'].head(5).iterrows():
        print(f"  - {row['title']}")
        