import ipaddress

# IP público
public_ip = ipaddress.IPv4Network("191.241.144.0/24")

# Tamanho da sub-rede (29 bits)
subnet_size = 29

# Calcula o número de sub-redes
num_subnets = 2 ** (public_ip.max_prefixlen - subnet_size)

# Lista para armazenar as sub-redes
subnets = []

# Gere as sub-redes
for i in range(num_subnets):
    subnet = public_ip.subnets(new_prefix=subnet_size)
    subnets.append(next(subnet))

# Imprima as sub-redes
for i, subnet in enumerate(subnets):
    print(f"Subnet {i+1}: {subnet}")
